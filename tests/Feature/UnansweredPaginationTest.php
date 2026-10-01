<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The list of questions the assistant could not answer.
 *
 * This screen exists so the business finds out what its customers are asking
 * and it cannot answer. The list was cut off at the fifty most asked, and a
 * cut-off list looks exactly like a complete one -- so past the fiftieth,
 * every question was dropped and nothing said it had been. These tests hold
 * the list to reporting the whole of what it found.
 */
class UnansweredPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'email' => 'boss@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Ana Reyes',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    /** @param  array<int,string>  $questions */
    private function asked(array $questions): ChatConversation
    {
        $conversation = ChatConversation::create([
            'visitor_token' => 'visitor-' . uniqid(),
            'last_message_at' => now(),
        ]);

        foreach ($questions as $question) {
            ChatMessage::create([
                'conversation_id' => $conversation->conversation_id,
                'role' => ChatMessage::ROLE_USER,
                'body' => $question,
                'intent_key' => null,
            ]);
        }

        return $conversation;
    }

    /** @return array<int,string> */
    private function distinctQuestions(int $count): array
    {
        return array_map(fn (int $n) => "what oil does vehicle number {$n} take", range(1, $count));
    }

    private function fetch(User $admin, string $query = '')
    {
        return $this->actingAs($admin, 'staff')
            ->getJson('/admin-api/assistant/unanswered' . ($query ? '?' . $query : ''))
            ->assertOk();
    }

    #[Test]
    public function a_hundred_unanswered_questions_are_all_reachable(): void
    {
        $admin = $this->admin();
        $this->asked($this->distinctQuestions(100));

        $first = $this->fetch($admin);

        // The whole count is reported, not the size of the page holding it.
        $this->assertSame(100, $first->json('meta.total'));

        $seen = [];
        $page = 1;

        do {
            $response = $this->fetch($admin, "page={$page}");

            foreach ($response->json('data') as $row) {
                $seen[] = $row['question'];
            }

            $page++;
        } while ($page <= $response->json('meta.last_page'));

        $this->assertCount(100, array_unique($seen), 'Some questions were never on any page.');
    }

    #[Test]
    public function nothing_is_silently_dropped_past_the_old_fifty_row_cap(): void
    {
        $admin = $this->admin();

        // The sixtieth question, which the capped list could never show.
        $this->asked($this->distinctQuestions(60));

        $response = $this->fetch($admin, 'per_page=100');

        $this->assertCount(60, $response->json('data'));
        $this->assertSame(60, $response->json('meta.total'));
    }

    #[Test]
    public function the_same_question_asked_many_times_is_one_row(): void
    {
        $admin = $this->admin();

        $this->asked(array_fill(0, 12, 'do you deliver to imus'));
        $this->asked(['do you have drums']);

        $response = $this->fetch($admin);

        $rows = $response->json('data');

        $this->assertCount(2, $rows, 'Repeats were listed separately instead of counted.');
        $this->assertSame(2, $response->json('meta.total'), 'The total counted messages, not questions.');

        $this->assertSame('do you deliver to imus', $rows[0]['question']);
        $this->assertSame(12, $rows[0]['times']);
    }

    #[Test]
    public function the_most_asked_question_leads_the_first_page(): void
    {
        $admin = $this->admin();

        $this->asked($this->distinctQuestions(40));
        $this->asked(array_fill(0, 5, 'is zinc additive something you sell'));

        $rows = $this->fetch($admin)->json('data');

        $this->assertSame('is zinc additive something you sell', $rows[0]['question']);
        $this->assertSame(5, $rows[0]['times']);
    }

    #[Test]
    public function the_pages_do_not_repeat_or_skip_a_question(): void
    {
        $admin = $this->admin();
        $this->asked($this->distinctQuestions(45));

        $first = $this->fetch($admin, 'per_page=20&page=1')->json('data');
        $second = $this->fetch($admin, 'per_page=20&page=2')->json('data');
        $third = $this->fetch($admin, 'per_page=20&page=3')->json('data');

        $this->assertCount(20, $first);
        $this->assertCount(20, $second);
        $this->assertCount(5, $third);

        $all = array_merge(
            array_column($first, 'question'),
            array_column($second, 'question'),
            array_column($third, 'question'),
        );

        $this->assertCount(45, array_unique($all), 'A question appeared on two pages or on none.');
    }

    #[Test]
    public function the_figures_above_the_list_describe_the_period_not_the_page(): void
    {
        $admin = $this->admin();
        $this->asked($this->distinctQuestions(30));

        $first = $this->fetch($admin, 'per_page=10&page=1');
        $last = $this->fetch($admin, 'per_page=10&page=3');

        // Reading page three does not make the shop look like it was asked
        // fewer questions than it was.
        $this->assertSame(30, $first->json('stats.asked'));
        $this->assertSame(30, $last->json('stats.asked'));
        $this->assertSame(30, $last->json('stats.missed'));
        $this->assertSame(1, $last->json('stats.conversations'));
    }

    /**
     * The card above the list sends the reader to the list, so it has to
     * count the same thing. It counted messages while the list counts
     * questions, which was invisible until the list started reporting its
     * own total directly beneath the card.
     */
    #[Test]
    public function the_unanswered_card_counts_the_rows_of_the_list_it_points_at(): void
    {
        $admin = $this->admin();

        $this->asked(array_fill(0, 4, 'do you deliver to imus'));
        $this->asked(['do you have drums']);

        $response = $this->fetch($admin);

        // Two questions, five messages.
        $this->assertSame(2, $response->json('meta.total'));
        $this->assertSame(5, $response->json('stats.missed'));

        $view = file_get_contents(resource_path('views/admin/chatbot.blade.php'));

        $this->assertStringContainsString("card('Unanswered', meta.total", $view);
        $this->assertStringNotContainsString("card('Unanswered', stats.missed", $view);
    }

    #[Test]
    public function a_page_beyond_the_last_one_is_empty_rather_than_an_error(): void
    {
        $admin = $this->admin();
        $this->asked($this->distinctQuestions(5));

        $response = $this->fetch($admin, 'page=99');

        $this->assertSame([], $response->json('data'));
        $this->assertSame(5, $response->json('meta.total'));
    }

    #[Test]
    public function an_unreadable_page_number_is_treated_as_the_first(): void
    {
        $admin = $this->admin();
        $this->asked($this->distinctQuestions(5));

        $response = $this->fetch($admin, 'page=' . urlencode("1' OR '1'='1"));

        $this->assertCount(5, $response->json('data'));
        $this->assertSame(1, $response->json('meta.current_page'));
    }

    #[Test]
    public function nobody_can_ask_for_the_whole_table_in_one_request(): void
    {
        $admin = $this->admin();
        $this->asked($this->distinctQuestions(200));

        $response = $this->fetch($admin, 'per_page=5000');

        $this->assertLessThanOrEqual(100, count($response->json('data')));
        $this->assertSame(200, $response->json('meta.total'));
    }

    #[Test]
    public function the_period_still_decides_what_is_counted(): void
    {
        $admin = $this->admin();

        $recent = $this->asked(['do you have 0w16']);
        $old = $this->asked(['what is the capital of france']);

        ChatMessage::where('conversation_id', $old->conversation_id)
            ->update(['created_at' => now()->subDays(120)]);

        $response = $this->fetch($admin, 'days=30');

        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('do you have 0w16', $response->json('data.0.question'));

        // And the long view finds both again.
        $this->assertSame(2, $this->fetch($admin, 'days=365')->json('meta.total'));

        $this->assertNotNull($recent->conversation_id);
    }

    #[Test]
    public function an_empty_period_reports_no_pages_rather_than_one_of_nothing(): void
    {
        $admin = $this->admin();

        $response = $this->fetch($admin, 'days=7');

        $this->assertSame([], $response->json('data'));
        $this->assertSame(0, $response->json('meta.total'));
    }

    #[Test]
    public function the_page_asks_for_a_page_and_draws_the_control(): void
    {
        $view = file_get_contents(resource_path('views/admin/chatbot.blade.php'));

        $this->assertStringContainsString('id="unansweredPagination"', $view);
        $this->assertStringContainsString("renderPagination('unansweredPagination'", $view);
        $this->assertStringContainsString('page=${page}', $view);

        // Changing the period has to return to the first page, or the reader
        // is left on a page number the new period may not have.
        $this->assertStringContainsString('onchange="loadUnanswered(1)"', $view);
    }

    #[Test]
    public function the_list_is_no_longer_capped_in_the_controller(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/ChatbotAdminController.php'));

        $unanswered = substr($source, (int) strpos($source, 'public function unanswered'));

        $this->assertStringNotContainsString('->limit(', $unanswered, 'The hard cap is back.');
        $this->assertStringContainsString('->paginate(', $unanswered);
    }

    /**
     * Two different refusals, and the difference is deliberate: a customer on
     * the staff guard is not staff at all, so AdminMiddleware signs them out
     * and answers 401 before any permission is consulted. A member of staff
     * who simply lacks this one permission stays signed in and gets 403.
     */
    #[Test]
    public function the_list_is_only_for_staff_who_may_see_it(): void
    {
        $this->getJson('/admin-api/assistant/unanswered')->assertUnauthorized();

        $customer = User::create([
            'email' => 'shopper@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Boy Santos',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->actingAs($customer, 'staff')
            ->getJson('/admin-api/assistant/unanswered')
            ->assertUnauthorized();

        // The assistant is the administrator's to edit, so other staff are
        // refused this screen rather than shown a list they cannot act on.
        $accounting = User::create([
            'email' => 'books@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Mia Cruz',
            'role' => User::ROLE_ACCOUNTING,
            'is_active' => true,
        ]);

        $this->actingAs($accounting, 'staff')
            ->getJson('/admin-api/assistant/unanswered')
            ->assertForbidden();
    }
}
