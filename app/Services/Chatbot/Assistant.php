<?php

namespace App\Services\Chatbot;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
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

    public function __construct(
        private readonly IntentMatcher $matcher,
        private readonly Responder $responder,
        private readonly VehicleMatcher $vehicles,
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

            if ($vehicle['matched'] !== null) {
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

        $reply = $intent
            ? $this->responder->answer($conversation, $intent, $message, $user)
            : $this->responder->fallback($result['suggestions'], $user);

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
