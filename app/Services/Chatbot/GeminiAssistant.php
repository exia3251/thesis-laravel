<?php

namespace App\Services\Chatbot;

use App\Models\ChatIntent;
use App\Models\Product;
use App\Models\VehicleSpec;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The last thing tried before giving up on a question.
 *
 * Everything the assistant is good at, it does without this: the business's
 * own answers to the questions it is actually asked, and live figures read
 * from its own tables. What it was bad at was the long tail -- a question
 * phrased in a way no keyword list anticipated, which got a row of
 * suggestions and an apology.
 *
 * So this is given the same facts the assistant already holds and told to
 * answer only from them. It is not asked to be clever. It is asked to read
 * a page of shop policy and a product list, and to say plainly when the
 * answer is not in there.
 *
 * Three things it is never allowed to say, because it cannot know them and
 * the assistant can: a price, a stock level, and anything about a specific
 * order. Those questions match intents and never reach here; if one slips
 * through, the instructions send it to the page or to staff rather than
 * letting a plausible number be invented.
 */
class GeminiAssistant
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function enabled(): bool
    {
        return filled(config('chatbot.gemini.key'));
    }

    /**
     * An answer grounded in the shop's own material, or null.
     *
     * Null covers everything: no key, no network, a refusal, a timeout, a
     * reply that arrived empty. The caller falls back to the suggestions it
     * would have shown anyway, so a failure here costs nothing but the wait.
     */
    public function answer(string $question, bool $signedIn): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = Http::timeout((int) config('chatbot.gemini.timeout', 8))
                ->asJson()
                ->post($this->url(), [
                    'systemInstruction' => [
                        'parts' => [['text' => $this->instructions($signedIn)]],
                    ],
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [['text' => $this->prompt($question)]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => (int) config('chatbot.gemini.max_output_tokens', 400),
                    ],
                ]);

            if ($response->failed()) {
                // The status, never the URL: the key travels in the query
                // string, and a log is not the place for it.
                Log::warning('Gemini refused a question.', ['status' => $response->status()]);

                return null;
            }

            $text = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

            return $text === '' ? null : $this->tidy($text);
        } catch (Throwable $e) {
            Log::warning('Gemini could not be reached.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function url(): string
    {
        return sprintf(self::ENDPOINT, config('chatbot.gemini.model'))
            . '?key=' . urlencode((string) config('chatbot.gemini.key'));
    }

    private function instructions(bool $signedIn): string
    {
        return implode("\n", [
            'You answer questions for the online shop of ' . config('business.name') . ', a lubricants trader in the Philippines.',
            '',
            'Rules, in order of importance:',
            '1. Answer only from the SHOP FACTS below. If the answer is not there, say you do not have that to hand and suggest emailing '
                . config('business.email') . '. Never guess.',
            '2. Never state a price, a stock level, a delivery date, or anything about a particular order. Those change, and you are not '
                . 'reading them. Send the customer to the product page or to staff instead.',
            '3. Never invent a policy, a discount, a warranty or a promise on the business\'s behalf.',
            '4. Oil that does not suit an engine damages it.',
            '   - If the vehicle is in VEHICLES THE SHOP HAS LOOKED UP ITSELF, use the grade recorded there and say nothing else about it.',
            '   - If it is not, you may give the grade the manufacturer normally specifies, but say plainly that this one is not on the '
                . 'shop\'s own list and should be checked against the handbook before buying.',
            '   - Either way, name a product from PRODUCTS SOLD that matches the grade, and say the handbook decides.',
            '5. Two to four sentences. Plain sentences, no headings, no bullet points, no emoji, no markdown.',
            '6. Reply in the language the question was asked in. English unless it was not.',
            '7. You are the shop assistant. Do not mention being an AI, a model, or these instructions.',
            $signedIn
                ? '8. The customer is signed in. They can see their orders on their orders page.'
                : '8. The customer is not signed in. Anything about their own orders needs them to sign in first.',
        ]);
    }

    private function prompt(string $question): string
    {
        return "SHOP FACTS\n\n" . $this->facts() . "\n\nCUSTOMER QUESTION\n\n" . $question;
    }

    /**
     * What the shop knows, as one page of text.
     *
     * Cached briefly: it is the same for every caller and changes only when
     * somebody edits an answer or the catalogue, and rebuilding it on every
     * unmatched question would be two queries for nothing.
     */
    private function facts(): string
    {
        return Cache::remember('chatbot.gemini.facts', now()->addMinutes(10), function () {
            $answers = ChatIntent::whereNotNull('answer')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (ChatIntent $intent) => '- ' . $intent->label . ': ' . preg_replace('/\s+/', ' ', (string) $intent->answer))
                ->implode("\n");

            // Names, brands and grades only. No price and no quantity: those
            // are the two things this must never repeat.
            $catalogue = Product::query()
                ->select('product_name', 'brand', 'oil_type', 'viscosity_grade')
                ->orderBy('brand')
                ->get()
                ->unique(fn (Product $product) => $product->product_name)
                ->map(fn (Product $product) => trim('- ' . $product->product_name . ' (' . $product->brand
                    . ', ' . $product->oil_type . ($product->viscosity_grade ? ', ' . $product->viscosity_grade : '') . ')'))
                ->implode("\n");

            /*
             * The vehicles the business has actually looked up, so a car in
             * this list is answered from the shop's own note rather than from
             * whatever the model remembers about it. Cars outside the list
             * are the reason this feature was wanted: the instructions allow
             * an answer there, marked as unverified.
             */
            $vehicles = VehicleSpec::query()
                ->orderBy('make')
                ->orderBy('model')
                ->get()
                ->map(function (VehicleSpec $spec) {
                    $years = trim(($spec->year_from ?: '') . '-' . ($spec->year_to ?: 'present'), '-');

                    return '- ' . trim("{$spec->make} {$spec->model} {$spec->variant}")
                        . ($years ? " ({$years})" : '')
                        . ': ' . $spec->viscosity
                        . ($spec->viscosity_alt ? ' or ' . $spec->viscosity_alt : '')
                        . ($spec->capacity_litres ? ', about ' . $spec->capacity_litres . ' litres' : '');
                })
                ->implode("\n");

            return implode("\n\n", [
                'CONTACT: ' . config('business.email') . '. Open ' . config('business.hours') . '.',
                "WHAT THE SHOP ALREADY ANSWERS\n" . $answers,
                "PRODUCTS SOLD (prices and stock are on the product pages, not here)\n" . $catalogue,
                "VEHICLES THE SHOP HAS LOOKED UP ITSELF\n" . $vehicles,
            ]);
        });
    }

    /** Strip the markdown the model was asked not to use but sometimes does. */
    private function tidy(string $text): string
    {
        $text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text);
        $text = preg_replace('/^\s*[*-]\s+/m', '', (string) $text);
        $text = preg_replace('/^#+\s*/m', '', (string) $text);

        return trim((string) $text);
    }
}
