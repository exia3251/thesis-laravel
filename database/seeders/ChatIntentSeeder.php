<?php

namespace Database\Seeders;

use App\Models\ChatIntent;
use Illuminate\Database\Seeder;

/**
 * The assistant's starting knowledge.
 *
 * Rows are matched on intent_key, so running this again refreshes the wording
 * of anything still at its default while leaving keywords and answers that
 * have since been edited in the back office alone -- see updateOrCreate below,
 * which only writes the columns an administrator is not expected to own.
 *
 * Answers may contain :placeholders, which are filled from configuration when
 * the reply is built, so the payment rules cannot drift from config/payments.php.
 */
class ChatIntentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->intents() as $intent) {
            ChatIntent::updateOrCreate(
                ['intent_key' => $intent['intent_key']],
                $intent
            );
        }
    }

    private function intents(): array
    {
        return [
            // ------------------------------------------------- orders (live)
            [
                'intent_key' => 'order_status',
                'category' => 'orders',
                'label' => 'Track my order',
                'keywords' => 'track status where when arrive arriving arrived shipped dispatch delivered receive parcel progress',
                'handler' => 'orderStatus',
                'requires_login' => true,
                'is_suggested' => true,
                'sort_order' => 10,
            ],
            [
                'intent_key' => 'order_balance',
                'category' => 'orders',
                'label' => 'How much do I owe?',
                'keywords' => 'balance owe owing due outstanding remaining unpaid much left total bayad utang magkano',
                'handler' => 'orderBalance',
                'requires_login' => true,
                'is_suggested' => true,
                'sort_order' => 20,
            ],
            [
                'intent_key' => 'payment_state',
                'category' => 'orders',
                'label' => 'Was my payment received?',
                'keywords' => 'payment paid settled received approved confirmed verify verified proof reference gcash sent receipt screenshot pending review',
                'handler' => 'paymentState',
                'requires_login' => true,
                'is_suggested' => false,
                'sort_order' => 30,
            ],
            [
                'intent_key' => 'order_cancel',
                'category' => 'orders',
                'label' => 'Can I cancel an order?',
                'keywords' => 'cancel cancellation stop call off refund money back',
                'handler' => 'orderCancel',
                'requires_login' => true,
                'is_suggested' => false,
                'sort_order' => 40,
            ],
            [
                'intent_key' => 'order_list',
                'category' => 'orders',
                'label' => 'Show my recent orders',
                'keywords' => 'orders recent history list purchases bought previous past',
                'handler' => 'orderList',
                'requires_login' => true,
                'is_suggested' => false,
                'sort_order' => 50,
            ],

            // ----------------------------------------------- products (flow)
            [
                'intent_key' => 'product_finder',
                'category' => 'products',
                'label' => 'Find the right oil',
                'keywords' => 'find recommend suggest choose looking oil engine lubricant motor grade viscosity buy',
                'handler' => 'productFinder',
                'is_suggested' => true,
                'sort_order' => 60,
            ],
            [
                'intent_key' => 'product_stock',
                'category' => 'products',
                'label' => 'What is in stock?',
                'keywords' => 'stock available availability supply inventory meron',
                'handler' => 'productStock',
                'is_suggested' => false,
                'sort_order' => 70,
            ],
            [
                'intent_key' => 'product_brands',
                'category' => 'products',
                'label' => 'Which brands do you carry?',
                'keywords' => 'brand brands carry manufacturer canroyal patrol solar',
                'handler' => 'productBrands',
                'is_suggested' => false,
                'sort_order' => 80,
            ],

            // --------------------------------------------- payments (static)
            [
                'intent_key' => 'payment_methods',
                'category' => 'payments',
                'label' => 'How can I pay?',
                'keywords' => 'pay payment method options cash card gcash cod cash on delivery installment bayad',
                'answer' => "We take GCash and cash on delivery. At checkout you choose one of three plans:\n\n"
                    . "• Cash on delivery — pay the whole amount when the order arrives.\n"
                    . "• GCash in full — pay online before we dispatch.\n"
                    . "• Split — a GCash down payment now, the balance in cash on delivery.\n\n"
                    . "We do not take credit or debit cards yet.",
                'is_suggested' => true,
                'sort_order' => 90,
            ],
            [
                'intent_key' => 'gcash_how',
                'category' => 'payments',
                'label' => 'How does GCash payment work?',
                'keywords' => 'gcash how send transfer reference number screenshot proof upload qr scan',
                'answer' => "Open the order from your Orders page and use the payment form there. You will need to:\n\n"
                    . "1. Send the amount to the GCash number shown on the page.\n"
                    . "2. Enter the reference number from your GCash receipt.\n"
                    . "3. Attach a screenshot of that receipt.\n\n"
                    . "A member of staff checks it by hand, usually the same working day. Your order updates as soon as it is approved.",
                'is_suggested' => false,
                'sort_order' => 100,
            ],
            [
                'intent_key' => 'down_payment',
                'category' => 'payments',
                'label' => 'What is the minimum down payment?',
                'keywords' => 'down payment downpayment minimum deposit split partial percent least initial',
                'answer' => "If you split payment, the GCash down payment has to be at least :down_payment_percent% of the order total. "
                    . "The rest is paid in cash when the order arrives.\n\n"
                    . "The checkout works out the exact figure for your basket, so you do not have to.",
                'is_suggested' => false,
                'sort_order' => 110,
            ],
            [
                'intent_key' => 'extra_payment',
                'category' => 'payments',
                'label' => 'Can I pay the balance early?',
                'keywords' => 'early settle advance extra additional pay off balance before delivery partial again more',
                'answer' => "Yes. Once your down payment has gone through you can keep paying down the balance by GCash before delivery.\n\n"
                    . "Each extra payment needs to be at least PHP :minimum_extra_payment, because a member of staff reviews every one. "
                    . "Paying off the whole remaining balance is always allowed, even if it comes to less than that.",
                'is_suggested' => false,
                'sort_order' => 120,
            ],

            // ------------------------------------- general knowledge (static)
            [
                'intent_key' => 'oil_type_difference',
                'category' => 'products',
                'label' => 'Synthetic or mineral?',
                'keywords' => 'synthetic mineral semi difference between which better type fully explain compare',
                'answer' => "The short version:\n\n"
                    . "• Mineral — refined from crude oil. The cheapest, and fine for older or low-mileage engines. Needs changing most often.\n"
                    . "• Semi-synthetic — a blend. A middle ground on both price and protection.\n"
                    . "• Fully synthetic — engineered rather than refined. Holds up best under heat and long drains, and costs the most.\n\n"
                    . "Whichever you pick, your engine's handbook states the grade it needs. That comes first.",
                'is_suggested' => false,
                'sort_order' => 130,
            ],
            [
                'intent_key' => 'viscosity_meaning',
                'category' => 'products',
                'label' => 'What does 5W-30 mean?',
                'keywords' => 'viscosity grade 5w30 15w40 10w40 sae number mean meaning weight thick thin w',
                'answer' => "It describes how the oil flows.\n\n"
                    . "The number before the W is how it behaves when cold — lower flows more easily on a cold start. "
                    . "The number after is how thick it stays at running temperature.\n\n"
                    . "So 5W-30 flows more easily cold than 15W-40, and 15W-40 stays thicker when hot. "
                    . "We carry 5W-30, 10W-40 and 15W-40. Your engine's handbook names the one it needs.",
                'is_suggested' => false,
                'sort_order' => 140,
            ],
            [
                'intent_key' => 'change_interval',
                'category' => 'products',
                'label' => 'How often should oil be changed?',
                'keywords' => 'how often change interval kilometers km months replace drain schedule when',
                'answer' => "It depends on the oil and how the vehicle is used. As a rough guide, mineral oil is usually changed sooner "
                    . "than fully synthetic, and stop-start city driving is harder on oil than open road.\n\n"
                    . "We would rather not guess for your engine. Your handbook gives the interval the manufacturer expects, and that is the one to follow.",
                'is_suggested' => false,
                'sort_order' => 150,
            ],

            // ------------------------- company details: replace with your own
            [
                'intent_key' => 'delivery_info',
                'category' => 'delivery',
                'label' => 'Where do you deliver?',
                'keywords' => 'deliver delivery area areas ship shipping location where region province fee charge how long days',
                'answer' => "[Replace this in Admin → Assistant.] Describe the areas you deliver to, how long delivery usually takes, "
                    . "and any delivery charge.",
                'is_suggested' => true,
                'sort_order' => 160,
            ],
            [
                'intent_key' => 'returns_policy',
                'category' => 'delivery',
                'label' => 'What is your returns policy?',
                'keywords' => 'return returns refund exchange wrong damaged faulty leaking replace policy warranty',
                'answer' => "[Replace this in Admin → Assistant.] Describe what a customer should do if an item arrives wrong or damaged, "
                    . "and the window they have to tell you.",
                'is_suggested' => false,
                'sort_order' => 170,
            ],
            [
                'intent_key' => 'business_hours',
                'category' => 'company',
                'label' => 'What are your opening hours?',
                'keywords' => 'hours open opening close closing time schedule weekend sunday saturday available contact reach phone email',
                'answer' => "[Replace this in Admin → Assistant.] Confirm your opening hours and the best number or email to reach you on. "
                    . "The footer currently says Monday to Saturday, 8am to 6pm.",
                'is_suggested' => false,
                'sort_order' => 180,
            ],
            [
                'intent_key' => 'bulk_orders',
                'category' => 'company',
                'label' => 'Do you supply businesses?',
                'keywords' => 'bulk wholesale fleet business drum barrel quote discount trade large quantity 200 liters commercial',
                'answer' => "[Replace this in Admin → Assistant.] Say whether you quote for fleets and workshops, what the minimum is, "
                    . "and how someone should get in touch. You already stock 20 and 200 litre drums, which is worth mentioning.",
                'is_suggested' => false,
                'sort_order' => 190,
            ],
        ];
    }
}
