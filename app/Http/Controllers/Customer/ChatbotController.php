<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Chatbot\Assistant;
use Illuminate\Http\Request;

/**
 * The storefront assistant.
 *
 * Open to signed-out visitors, because most of what it answers -- payment
 * options, delivery, which oil -- is the sort of thing someone asks before
 * they have an account. Anything about a specific order is gated inside the
 * responder, which refuses without a signed-in user rather than trusting
 * whatever the browser sent.
 *
 * The visitor token lives in the session rather than in the request, so one
 * browser cannot read another visitor's conversation by guessing it.
 */
class ChatbotController extends Controller
{
    private const SESSION_KEY = 'chat_visitor_token';

    public function __construct(private readonly Assistant $assistant)
    {
    }

    public function open(Request $request)
    {
        $conversation = $this->assistant->conversationFor($request->user(), $this->token($request));

        return response()->json([
            'success' => true,
            'data' => $this->assistant->open($conversation, $request->user()),
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'intent' => 'nullable|string|max:60',
        ], [
            'message.required' => 'Type a question first.',
            'message.max' => 'That is a little long. Try asking in under 500 characters.',
        ]);

        $conversation = $this->assistant->conversationFor($request->user(), $this->token($request));

        $messages = $this->assistant->send(
            $conversation,
            $request->input('message'),
            $request->input('intent'),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data' => ['messages' => $messages],
        ]);
    }

    public function reset(Request $request)
    {
        $conversation = $this->assistant->conversationFor($request->user(), $this->token($request));

        return response()->json([
            'success' => true,
            'data' => $this->assistant->reset($conversation, $request->user()),
        ]);
    }

    private function token(Request $request): string
    {
        $token = $request->session()->get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = Assistant::newVisitorToken();
            $request->session()->put(self::SESSION_KEY, $token);
        }

        return $token;
    }
}
