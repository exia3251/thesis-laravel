<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChatIntent;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The back office side of the storefront assistant: editing what it knows,
 * and reading what it failed to answer.
 *
 * The unanswered list is the point of the whole screen. It is the only place
 * the business finds out what customers are actually asking, and every entry
 * is either a keyword the matcher is missing or a question worth adding.
 */
class ChatbotAdminController extends Controller
{
    public function index()
    {
        return view('admin.chatbot');
    }

    public function intents()
    {
        $intents = ChatIntent::orderBy('category')->orderBy('sort_order')->get()
            ->map(fn (ChatIntent $intent) => $intent->toArray() + [
                // How often this answer has actually been used, which says
                // more about an intent's worth than its position in a list.
                'uses' => ChatMessage::where('role', ChatMessage::ROLE_BOT)
                    ->where('intent_key', $intent->intent_key)->count(),
                'is_placeholder' => str_starts_with((string) $intent->answer, '[Replace this'),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'intents' => $intents,
                'categories' => $intents->pluck('category')->unique()->sort()->values(),
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $intent = ChatIntent::find($id);

        if (!$intent) {
            return response()->json(['success' => false, 'message' => 'That entry no longer exists.'], 404);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:120',
            'keywords' => 'required|string|max:1000',
            'answer' => 'nullable|string|max:4000',
            'is_active' => 'nullable|boolean',
            'is_suggested' => 'nullable|boolean',
        ], [
            'keywords.required' => 'Give it at least one trigger word, or nothing will ever match it.',
            'answer.max' => 'Answers are capped at 4,000 characters. A shorter one reads better in a chat bubble.',
        ]);

        // An intent backed by a handler builds its own reply, so its answer
        // column is meaningless and is not writable from here.
        if (filled($intent->handler)) {
            unset($validated['answer']);
        } elseif (blank($validated['answer'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'This entry has no handler, so it needs an answer.',
            ], 422);
        }

        $intent->update($validated + [
            'is_active' => $request->boolean('is_active'),
            'is_suggested' => $request->boolean('is_suggested'),
        ]);

        ActivityLog::logAction(
            auth()->id(),
            'chatbot_intent_updated',
            auth()->user()->full_name . " edited the assistant's answer for \"{$intent->label}\""
        );

        return response()->json(['success' => true, 'message' => 'Saved.']);
    }

    /**
     * Questions the matcher could not place, most common first, so the same
     * question asked twenty times is one row rather than twenty.
     */
    public function unanswered(Request $request)
    {
        $request->validate(['days' => 'nullable|integer|min:1|max:365']);

        $since = now()->subDays((int) $request->integer('days', 90));

        $rows = ChatMessage::unanswered()
            ->where('created_at', '>=', $since)
            ->selectRaw('LOWER(TRIM(body)) AS question, COUNT(*) AS times, MAX(created_at) AS last_asked')
            ->groupBy('question')
            ->orderByDesc('times')
            ->orderByDesc('last_asked')
            ->limit(50)
            ->get()
            ->map(fn ($row) => [
                'question' => $row->question,
                'times' => (int) $row->times,
                'last_asked' => \Illuminate\Support\Carbon::parse($row->last_asked)->diffForHumans(),
            ]);

        $totals = DB::table('chat_messages')
            ->where('role', ChatMessage::ROLE_USER)
            ->where('created_at', '>=', $since)
            ->selectRaw('COUNT(*) AS asked, SUM(intent_key IS NULL) AS missed')
            ->first();

        $asked = (int) ($totals->asked ?? 0);
        $missed = (int) ($totals->missed ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'questions' => $rows,
                'asked' => $asked,
                'missed' => $missed,
                'answered_percent' => $asked > 0 ? round((($asked - $missed) / $asked) * 100, 1) : null,
                'conversations' => DB::table('chat_conversations')->where('last_message_at', '>=', $since)->count(),
            ],
        ]);
    }
}
