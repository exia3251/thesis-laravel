<?php

namespace App\Services\Chatbot;

use App\Models\ChatIntent;
use Illuminate\Support\Collection;

/**
 * Decides which intent a message is asking about.
 *
 * Scoring is weighted by how many intents a keyword appears in, so a word
 * that half the table lists -- "order", "payment" -- counts for little, while
 * a word only one intent claims -- "gcash", "viscosity" -- is close to
 * decisive. Without that, the intents with the longest keyword lists would
 * win nearly everything.
 *
 * Nothing here is clever enough to be trusted blindly, so a match has to beat
 * a floor before it is used. Below it the caller offers suggestions instead of
 * guessing, which is the failure a rule-based assistant should have.
 */
class IntentMatcher
{
    /**
     * Minimum score before a match is considered real rather than noise.
     *
     * Set below the weight of a single distinctive keyword on purpose: "how
     * can I pay" carries exactly one content word, and a floor above it sent
     * a perfectly clear question to the fallback.
     */
    private const SCORE_FLOOR = 0.55;

    /** Words too short to be worth spell-checking. */
    private const MIN_FUZZY_LENGTH = 5;

    /**
     * Words that make a question about the asker's own records rather than
     * about the shop. "Where is my order" and "do you deliver to Cavite"
     * share the word delivery; only one of them is personal.
     *
     * Genuine possessives only. "I" and "me" look personal but are not --
     * "how can I pay" is a question about the shop, and treating its "I" as
     * ownership sent it to the balance handler.
     */
    private const POSSESSIVES = ['my', 'mine', 'ko', 'akin', 'aking', 'ako'];

    private const POSSESSIVE_BONUS = 0.6;

    /**
     * Words carrying no signal, stripped from keyword lists before scoring.
     *
     * An administrator editing an intent will naturally write "in stock" or
     * "do you have", and without this the stray "in" and "have" would match
     * half the questions asked. They stay in the visitor's own tokens, since
     * the possessive rule above reads them.
     */
    private const NOISE = [
        'a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'been', 'am',
        'do', 'does', 'did', 'have', 'has', 'had', 'can', 'could', 'will',
        'would', 'should', 'to', 'of', 'in', 'on', 'at', 'for', 'and', 'or',
        'it', 'this', 'that', 'there', 'you', 'your', 'we', 'us', 'if', 'so',
        'i', 'me', 'im', 'ive', 'my',
        // Question words carry no subject. "Where" and "when" are kept, since
        // they genuinely point at status and delivery questions.
        'how', 'what', 'which', 'why', 'who', 'whose', 'does', 'about',
    ];

    private ?Collection $intents = null;
    private array $weights = [];

    /**
     * @return array{intent: ?ChatIntent, score: float, suggestions: Collection}
     */
    public function match(string $message, bool $isSignedIn): array
    {
        $tokens = $this->tokenise($message);

        if ($tokens === []) {
            return ['intent' => null, 'score' => 0.0, 'suggestions' => $this->suggestions($isSignedIn)];
        }

        $scored = $this->candidates()
            ->map(fn (ChatIntent $intent) => [
                'intent' => $intent,
                'score' => $this->score($intent, $tokens),
                'sharpest' => $this->sharpest($intent, $tokens),
            ])
            ->filter(fn (array $row) => $row['score'] > 0)
            // Two intents can total the same score from different keywords.
            // The one whose single best keyword is rarer is the better guess,
            // so "cancel" beats "order" rather than losing on table order.
            ->sortByDesc(fn (array $row) => [$row['score'], $row['sharpest']])
            ->values();

        $best = $scored->first();

        if (!$best || $best['score'] < self::SCORE_FLOOR) {
            // Nothing convincing. Offer the nearest few as chips rather than
            // an apology, so there is always a way forward.
            $near = $scored->take(3)->map(fn (array $row) => $row['intent']);

            return [
                'intent' => null,
                'score' => $best['score'] ?? 0.0,
                'suggestions' => $near->isNotEmpty() ? $near : $this->suggestions($isSignedIn),
            ];
        }

        return [
            'intent' => $best['intent'],
            'score' => $best['score'],
            'suggestions' => $scored->skip(1)->take(2)->map(fn (array $row) => $row['intent']),
        ];
    }

    /** The chips shown when a conversation opens. */
    public function suggestions(bool $isSignedIn): Collection
    {
        return $this->candidates()
            ->filter(fn (ChatIntent $intent) => $intent->is_suggested)
            ->filter(fn (ChatIntent $intent) => $isSignedIn || !$intent->requires_login)
            ->sortBy('sort_order')
            ->take(4)
            ->values();
    }

    public function find(string $key): ?ChatIntent
    {
        return $this->candidates()->firstWhere('intent_key', $key);
    }

    private function candidates(): Collection
    {
        return $this->intents ??= ChatIntent::active()->orderBy('sort_order')->get();
    }

    /**
     * Lower-case, strip punctuation, and fold viscosity grades so that
     * "5W-30", "5w 30" and "5w30" all reach the keyword list as one token.
     */
    private function tokenise(string $message): array
    {
        $text = mb_strtolower(trim($message));
        $text = preg_replace('/(\d+)\s*w\s*[-\s]?\s*(\d+)/u', '$1w$2', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);

        $tokens = array_filter(preg_split('/\s+/u', $text) ?: []);

        return array_values(array_unique($tokens));
    }

    /** An intent's keywords with the noise words taken out. */
    private function keywordsOf(ChatIntent $intent): array
    {
        // Possessives are excluded here as well: they are read by the rule
        // above, and as plain keywords they would make "my" alone decisive.
        return array_values(array_unique(array_diff($intent->keywordList(), self::NOISE, self::POSSESSIVES)));
    }

    /**
     * How often each keyword appears across the whole table. A keyword listed
     * by one intent is worth much more than one listed by eight.
     */
    private function weights(): array
    {
        if ($this->weights !== []) {
            return $this->weights;
        }

        $counts = [];

        foreach ($this->candidates() as $intent) {
            foreach ($this->keywordsOf($intent) as $keyword) {
                $counts[$keyword] = ($counts[$keyword] ?? 0) + 1;
            }
        }

        $total = max($this->candidates()->count(), 1);

        foreach ($counts as $keyword => $count) {
            // Ranges from 1.0 for a keyword only one intent claims, down
            // towards 0 for one nearly all of them share.
            $this->weights[$keyword] = log(1 + ($total / $count)) / log(1 + $total);
        }

        return $this->weights;
    }

    private function score(ChatIntent $intent, array $tokens): float
    {
        $weights = $this->weights();
        $keywords = $this->keywordsOf($intent);
        $score = 0.0;

        foreach ($keywords as $keyword) {
            $weight = $weights[$keyword] ?? 1.0;

            if (in_array($keyword, $tokens, true)) {
                $score += $weight;
                continue;
            }

            // One transposed or missing letter still counts, at a discount,
            // so "gcsh" and "delivary" do not fall through to the fallback.
            if (mb_strlen($keyword) >= self::MIN_FUZZY_LENGTH && $this->nearMiss($keyword, $tokens)) {
                $score += $weight * 0.6;
            }
        }

        // Only applied once the intent has matched on its own merits, so a
        // bare "my" cannot pull an unrelated intent up from nothing.
        if ($score > 0 && $intent->requires_login && array_intersect(self::POSSESSIVES, $tokens)) {
            $score += self::POSSESSIVE_BONUS;
        }

        return round($score, 4);
    }

    /** The weight of the rarest keyword this message actually hit. */
    private function sharpest(ChatIntent $intent, array $tokens): float
    {
        $weights = $this->weights();
        $best = 0.0;

        foreach ($this->keywordsOf($intent) as $keyword) {
            if (in_array($keyword, $tokens, true)) {
                $best = max($best, $weights[$keyword] ?? 1.0);
            }
        }

        return $best;
    }

    private function nearMiss(string $keyword, array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (abs(mb_strlen($token) - mb_strlen($keyword)) > 1) {
                continue;
            }

            if (levenshtein($token, $keyword) === 1) {
                return true;
            }
        }

        return false;
    }
}
