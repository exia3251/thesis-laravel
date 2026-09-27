<?php

namespace App\Services\Chatbot;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Holds a conversation together: finds or starts one, records both sides of
 * every exchange, and decides whether an incoming message belongs to a
 * running flow or is a fresh question.
 */
class Assistant
{
    /** How much of the history the widget reloads on each page view. */
    private const HISTORY_LIMIT = 30;

    /**
     * The handlers that answer from this shop's own rows, and are therefore
     * never handed to a model.
     *
     * Two kinds. An order, a balance, a payment: facts about one customer
     * that exist nowhere else. And stock, brands and the product finder:
     * these draw cards with a live price and a live quantity on them, which
     * is a better answer than a sentence about the same thing.
     *
     * vehicleOil is deliberately absent. A car the shop has looked up is
     * still answered from the table, by the confident match further up --
     * but a car it has not is the whole reason a model was wanted here, and
     * the old reply to those was that we could not help.
     */
    private const READS_THE_DATABASE = [
        'orderStatus', 'orderBalance', 'paymentState', 'orderCancel', 'orderList',
        'productStock', 'productBrands', 'productFinder',
    ];

    public function __construct(
        private readonly IntentMatcher $matcher,
        private readonly Responder $responder,
        private readonly VehicleMatcher $vehicles,
        private readonly GroqAssistant $ai,
    ) {
    }

    public static function newVisitorToken(): string
    {
        return Str::random(40);
    }

    /**
     * The conversation for this visitor, started if there is not one already.
     *
     * Signing in mid-conversation adopts the anonymous thread rather than
     * abandoning it, so what was said before the sign-in is still on screen.
     */
    public function conversationFor(?User $user, string $visitorToken): ChatConversation
    {
        $conversation = ChatConversation::where('visitor_token', $visitorToken)->first();

        if ($conversation) {
            if ($user && $conversation->user_id === null) {
                $conversation->update(['user_id' => $user->user_id]);
            }

            return $conversation;
        }

        return ChatConversation::create([
            'user_id' => $user?->user_id,
            'visitor_token' => $visitorToken,
            'last_message_at' => now(),
        ]);
    }

    /**
     * Everything the widget needs to draw itself, including the opening
     * greeting when the conversation is new.
     */
    public function open(ChatConversation $conversation, ?User $user): array
    {
        $history = $conversation->messages()
            ->orderByDesc('message_id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->sortBy('message_id')
            ->values();

        if ($history->isEmpty()) {
            $greeting = $this->responder->greeting($user);
            $history = collect([$this->record($conversation, ChatMessage::ROLE_BOT, $greeting['body'], null, $greeting['payload'])]);
        }

        return [
            'messages' => $history->map(fn (ChatMessage $m) => $this->present($m))->all(),
            'signed_in' => (bool) $user,
        ];
    }

    /** @return array<int,array> the pair of messages this exchange produced */
    public function send(ChatConversation $conversation, string $message, ?string $intentKey, ?User $user): array
    {
        $message = trim($message);

        // A running flow gets first refusal on the message. Each one declines
        // anything that is not an answer to the step it asked, so a visitor
        // who changes the subject mid-flow is not trapped in it.
        foreach (['product_finder' => 'continueFinder', 'vehicle_oil' => 'continueVehicle'] as $key => $method) {
            $flowReply = $this->responder->{$method}($conversation, $message);

            if ($flowReply !== null) {
                $userMessage = $this->record($conversation, ChatMessage::ROLE_USER, $message, $key);
                $botMessage = $this->record($conversation, ChatMessage::ROLE_BOT, $flowReply['body'], $key, $flowReply['payload']);

                return [$this->present($userMessage), $this->present($botMessage)];
            }
        }

        // A recognised model name answers the question on its own, and no
        // keyword list could hold 55 of them. Tried before intent matching
        // because "what oil for my Vios" would otherwise start the generic
        // product finder; the matcher refuses anything that is not clearly
        // about a vehicle, so ordinary questions fall straight through.
        if (!$intentKey) {
            $vehicle = $this->vehicles->findConfident($message);

            /*
             * Only when the guide actually covers the car. A model it holds
             * for one generation is not an answer about another: an owner who
             * says 1995 and is told what the 2014 car takes has been given a
             * wrong grade with every appearance of a right one. Those fall
             * through to the model below, which can say what that generation
             * took and that the shop has not checked it.
             */
            if ($vehicle['matched'] !== null && ! $vehicle['outside_years']) {
                $reply = $this->responder->vehicleOil($conversation, $message, $user);

                $userMessage = $this->record($conversation, ChatMessage::ROLE_USER, $message, 'vehicle_oil');
                $botMessage = $this->record($conversation, ChatMessage::ROLE_BOT, $reply['body'], 'vehicle_oil', $reply['payload']);

                return [$this->present($userMessage), $this->present($botMessage)];
            }
        }

        // Reaching here with a flow still set means the subject changed.
        if ($conversation->flow()) {
            $conversation->clearContext();
        }

        // A chip carries its intent, so there is nothing to guess.
        $intent = $intentKey ? $this->matcher->find($intentKey) : null;
        $result = null;

        if (!$intent) {
            $result = $this->matcher->match($message, (bool) $user);
            $intent = $result['intent'];
        }

        $userMessage = $this->record($conversation, ChatMessage::ROLE_USER, $message, $intent?->intent_key);

        /*
         * Who answers, in order.
         *
         * A question that needs a figure out of the database -- where is my
         * order, how much do I owe, what is in stock -- is answered by the
         * handler that reads it. Those are facts about this customer and this
         * shelf on this afternoon. No model is told them, and none should
         * guess at them.
         *
         * Everything else goes to Groq, holding the shop's own answers, the
         * catalogue arranged by viscosity grade, and the vehicle table. It
         * reaches questions the keyword list never could: whether a car the
         * shop has never looked up suits an oil on the shelf, a question
         * phrased sideways, two questions at once.
         *
         * When it is switched off, unreachable, or declines, the keyword
         * assistant answers exactly as it did before. That path is not a
         * leftover -- it is what runs on a laptop with no internet, which is
         * how this will be demonstrated.
         *
         * The user's message keeps its intent_key either way, so a question
         * the intent list does not cover still shows in Admin -> Assistant as
         * one worth writing an answer for. Being handled is not the same as
         * being anticipated.
         */
        /*
         * A car the shop cannot look up still goes to Groq, even when the
         * word "oil" in the question pulls it towards the product finder.
         * "What oil does a Ferrari 488 take" was being answered with "what
         * kind of product are you after", which is the finder doing its job
         * on a question that was never about browsing.
         *
         * Only reached when the confident match above has already failed, so
         * a car the shop does know is never diverted here.
         */
        $unmatchedVehicle = $this->vehicles->mentionsAnyMake($message);

        /*
         * A grade the shop does not stock is answered from the shelf, by name.
         *
         * "Do you have 0W-16 for my hybrid" was starting the product finder,
         * which replied by asking what sort of product they were after -- the
         * thing they had just said. The catalogue knows the answer outright,
         * so no model is asked: a plain no and a list of what there is beats
         * anything a model could add, and the one thing it might add -- use
         * this other grade instead -- is the one thing that must not be said.
         */
        $unstockedGrades = $this->gradesWeDoNotStock($message);

        /*
         * A message aimed at the model rather than the shop is answered by
         * neither half. It is never sent, and it is not keyword-matched either:
         * "ignore previous instructions" matched "previous" and came back with
         * an invitation to sign in and look at past orders.
         */
        $talkingToTheModel = $result !== null && $this->ai->refuses($message);

        $suggestions = $result['suggestions'] ?? collect();

        $needsLiveData = $intent
            && ! $unmatchedVehicle
            && in_array($intent->handler, self::READS_THE_DATABASE, true);

        $answeredHere = $needsLiveData || $talkingToTheModel || $unstockedGrades->isNotEmpty();

        $aiAnswer = $answeredHere ? null : $this->ai->answer($message, (bool) $user);

        $reply = match (true) {
            $talkingToTheModel => $this->responder->fallback($suggestions, $user),
            $unstockedGrades->isNotEmpty() => $this->responder->gradeNotCarried($unstockedGrades, $suggestions, $user),
            $needsLiveData => $this->responder->answer($conversation, $intent, $message, $user),
            $aiAnswer !== null => ['body' => $aiAnswer, 'payload' => ['source' => 'groq']],

            /*
             * A car, and nothing from the model -- no key, no internet, or the
             * free allowance spent. The vehicle handler says what it can about
             * a car it does not hold; the product finder, which is what used
             * to catch these, replies by asking what kind of product they are
             * after, to somebody who has just named their car.
             */
            $unmatchedVehicle => $this->responder->vehicleOil($conversation, $message, $user),

            (bool) $intent => $this->responder->answer($conversation, $intent, $message, $user),
            default => $this->responder->fallback($suggestions, $user),
        };

        $botMessage = $this->record(
            $conversation,
            ChatMessage::ROLE_BOT,
            $reply['body'],
            $intent?->intent_key,
            $reply['payload'] ?? []
        );

        return [$this->present($userMessage), $this->present($botMessage)];
    }

    public function reset(ChatConversation $conversation, ?User $user): array
    {
        $conversation->messages()->delete();
        $conversation->clearContext();

        return $this->open($conversation, $user);
    }

    /**
     * The viscosity grades a message names that are not on the shelf.
     *
     * Read however people write one -- 5W-30, 5w30, 0W16 -- and compared
     * against the grades the catalogue holds, with the hyphen taken out of
     * both so the two spellings cannot disagree. What comes back is what was
     * asked for, spelled as they spelled it, so the reply can name it.
     *
     * Cached for the same reason the assistant's other facts are: it is the
     * same for every visitor and changes only when the catalogue does.
     *
     * @return Collection<int,string>
     */
    private function gradesWeDoNotStock(string $message): Collection
    {
        if (! preg_match_all('/\b(\d{1,2})w-?(\d{1,2})\b/i', $message, $found, PREG_SET_ORDER)) {
            return collect();
        }

        $carried = Cache::remember('chatbot.grades', now()->addMinutes(10), fn () => Product::query()
            ->whereNotNull('viscosity_grade')
            ->distinct()
            ->pluck('viscosity_grade')
            ->map(fn ($grade) => str_replace('-', '', mb_strtolower((string) $grade)))
            ->all());

        return collect($found)
            ->reject(fn (array $grade) => in_array(
                mb_strtolower($grade[1] . 'w' . $grade[2]),
                $carried,
                true
            ))
            ->map(fn (array $grade) => mb_strtoupper($grade[1] . 'W-' . $grade[2]))
            ->unique()
            ->values();
    }

    private function record(ChatConversation $conversation, string $role, string $body, ?string $intentKey, array $payload = []): ChatMessage
    {
        $conversation->forceFill(['last_message_at' => now()])->save();

        return ChatMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => $role,
            'body' => $body,
            'intent_key' => $intentKey,
            'payload' => $payload ?: null,
        ]);
    }

    private function present(ChatMessage $message): array
    {
        return [
            'id' => $message->message_id,
            'role' => $message->role,
            'body' => $message->body,
            'payload' => $message->payload ?? [],
            'at' => $message->created_at?->format('g:i A'),
        ];
    }
}
