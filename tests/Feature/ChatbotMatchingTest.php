<?php

namespace Tests\Feature;

use App\Services\Chatbot\IntentMatcher;
use Database\Seeders\ChatIntentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The assistant's routing, pinned.
 *
 * Every case here is a phrasing that was wrong at some point while the
 * matcher was being built, so the file doubles as the record of why the
 * scoring rules are the shape they are. Adding a keyword to one intent can
 * quietly steal questions from another, and this is what catches that.
 */
class ChatbotMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChatIntentSeeder::class);
    }

    public static function phrasings(): array
    {
        return [
            // question                              signed in  expected intent
            'balance in plain words'            => ['how much do i still owe', true, 'order_balance'],
            'balance in Filipino'               => ['magkano pa utang ko', true, 'order_balance'],
            'tracking'                          => ['where is my order', true, 'order_status'],
            'tracking by delivery wording'      => ['when will my order be delivered', true, 'order_status'],
            'cancelling'                        => ['can i cancel my order', true, 'order_cancel'],
            'payment state'                     => ['did my gcash payment go through', true, 'payment_state'],
            'payment state as a paid question'  => ['is my order paid', true, 'payment_state'],
            'order history'                     => ['show my recent orders', true, 'order_list'],

            // "I" is not a possessive: this is about the shop, not the asker
            'how to pay'                        => ['how can i pay', false, 'payment_methods'],
            'payment methods'                   => ['what payment methods do you accept', false, 'payment_methods'],
            'gcash process'                     => ['how do i send gcash proof', false, 'gcash_how'],
            'down payment rule'                 => ['whats the minimum downpayment', false, 'down_payment'],
            'paying early'                      => ['can i pay the balance early', true, 'extra_payment'],

            // delivery is a general question even though orders mention it
            'delivery areas'                    => ['do you deliver to cavite', false, 'delivery_info'],
            'delivery areas misspelt'           => ['delivary areas', false, 'delivery_info'],
            'shipping wording'                  => ['what areas do you ship to', false, 'delivery_info'],
            'delivery charge'                   => ['is there a delivery fee', false, 'delivery_info'],

            // How long delivery takes is its own intent, because this system
            // records no expected date and the courier sets the pace. These
            // must not fall to delivery_info, which the business fills in and
            // could put a figure in, nor to order_status, which would answer
            // a signed-out visitor with a sign-in prompt.
            'how long, plainly'                 => ['how long does delivery take', false, 'delivery_timing'],
            'how long, in days'                 => ['how many days is delivery', false, 'delivery_timing'],
            'how long, no possessive'           => ['how soon will it be delivered', false, 'delivery_timing'],
            'how long, in Filipino'             => ['gaano katagal ang delivery', false, 'delivery_timing'],
            'asking if it is quick'             => ['is delivery fast', false, 'delivery_timing'],

            // Mentioning a car routes to the vehicle guide, which can give a
            // grade, rather than the generic finder, which asks the customer
            // to already know what type they want.
            'a question about a vehicle'        => ['i need oil for my car', false, 'vehicle_oil'],
            'asking for help choosing'          => ['help me choose an oil', false, 'product_finder'],
            'browsing without a vehicle'        => ['what oil should i use', false, 'product_finder'],
            'stock'                             => ['what do you have in stock', false, 'product_stock'],
            'brands'                            => ['what brands do you have', false, 'product_brands'],
            'grades explained'                  => ['what does 5W-30 mean', false, 'viscosity_meaning'],
            'oil types compared'                => ['synthetic vs mineral', false, 'oil_type_difference'],
            'change interval'                   => ['how often should i change oil', false, 'change_interval'],
            'bulk supply'                       => ['do you sell in bulk', false, 'bulk_orders'],
            'opening hours'                     => ['what are your opening hours', false, 'business_hours'],
            'returns'                           => ['whats your returns policy', false, 'returns_policy'],
        ];
    }

    #[Test]
    #[DataProvider('phrasings')]
    public function it_routes_a_question_to_the_right_intent(string $question, bool $signedIn, string $expected): void
    {
        $result = (new IntentMatcher)->match($question, $signedIn);

        $this->assertNotNull($result['intent'], "Nothing matched \"{$question}\".");
        $this->assertSame($expected, $result['intent']->intent_key, "\"{$question}\" went to the wrong intent.");
    }

    public static function nonsense(): array
    {
        return [
            'keyboard mash' => ['asdkjh qwe zxc'],
            'a greeting'    => ['hello there'],
            'nothing'       => [''],
            'punctuation'   => ['???'],
        ];
    }

    #[Test]
    #[DataProvider('nonsense')]
    public function it_refuses_to_guess_when_nothing_fits(string $question): void
    {
        $result = (new IntentMatcher)->match($question, false);

        $this->assertNull($result['intent'], "\"{$question}\" should not have matched anything.");
    }

    #[Test]
    public function a_signed_out_visitor_is_not_offered_questions_about_their_own_orders(): void
    {
        $suggested = (new IntentMatcher)->suggestions(false);

        $this->assertNotEmpty($suggested);
        $this->assertTrue(
            $suggested->every(fn ($intent) => !$intent->requires_login),
            'A signed-out visitor was offered a question they cannot use.'
        );
    }

    #[Test]
    public function a_signed_in_customer_is_offered_their_orders(): void
    {
        $suggested = (new IntentMatcher)->suggestions(true);

        $this->assertTrue(
            $suggested->contains(fn ($intent) => $intent->requires_login),
            'A signed-in customer was not offered anything about their own orders.'
        );
    }

    #[Test]
    public function an_unmatched_question_still_comes_back_with_somewhere_to_go(): void
    {
        $result = (new IntentMatcher)->match('asdkjh qwe zxc', false);

        $this->assertNull($result['intent']);
        $this->assertNotEmpty($result['suggestions'], 'A dead end was offered no suggestions.');
    }
}
