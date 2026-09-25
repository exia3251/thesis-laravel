<?php

namespace Database\Seeders;

use App\Models\ChatIntent;
use Illuminate\Database\Seeder;

/**
 * The assistant's starting knowledge.
 *
 * Rows are matched on intent_key, so running this again rewrites the wording
 * of every answer here. It does not spare one edited in Admin -> Assistant:
 * updateOrCreate writes the whole row, answer included. Nothing in the table
 * records which answers a person has touched, so the seeder cannot tell them
 * apart -- worth knowing before re-running it against a live database.
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
                'intent_key' => 'vehicle_oil',
                'category' => 'products',
                'label' => 'What oil does my car take?',
                'keywords' => 'car vehicle sasakyan kotse makina engine takes fits suits compatible model make year owner handbook manual',
                'handler' => 'vehicleOil',
                'is_suggested' => true,
                'sort_order' => 55,
            ],
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
                // "supply" moved to bulk_orders: "do you supply businesses"
                // is a question about trade quantities, not about whether
                // a particular oil is on the shelf, and it was pulling
                // every such question here.
                'keywords' => 'stock available availability inventory meron',
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
                    . "The checkout works out the exact figure for your basket, so you do not have to.\n\n"
                    . "You can send it in one transfer or in parts of at least PHP :minimum_extra_payment each. "
                    . "That matters on a large order, because a GCash wallet will not send more than its own limit in one go. "
                    . "Every part needs its own reference number and screenshot, and we check each one.\n\n"
                    . "Where a balance is left to settle afterwards, the usual window is :grace_days_min to :grace_days_max days, "
                    . "agreed with our staff when you order.",
                'is_suggested' => false,
                'sort_order' => 110,
            ],
            [
                'intent_key' => 'extra_payment',
                'category' => 'payments',
                'label' => 'Can I pay the balance early?',
                'keywords' => 'early settle advance extra additional pay off balance before delivery partial again more',
                'answer' => "Yes. You can pay down what you owe by GCash at any point before delivery, whether or not the "
                    . "down payment is settled yet.\n\n"
                    . "Each payment needs to be at least PHP :minimum_extra_payment, because a member of staff reviews every one. "
                    . "Paying off the whole remaining balance is always allowed, even if it comes to less than that.\n\n"
                    . "One at a time: while a payment is waiting to be checked, the form will not take another.",
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
                // Timing words are deliberately absent. They belong to
                // delivery_timing below, which answers them without a figure.
                'keywords' => 'deliver delivery area areas ship shipping location where region province fee charge cover serve '
                    . 'philippines nationwide countrywide abroad international overseas luzon visayas mindanao provincial',
                /*
                 * The business's own answer: the whole country and nothing
                 * outside it.
                 *
                 * The line about the total is a statement about this system
                 * rather than a promise on their behalf -- there is no
                 * delivery charge anywhere in the schema, so an order total
                 * is the goods and nothing else. Whatever is arranged with
                 * the courier afterwards is between the customer and the
                 * staff, which is what the last line leaves room for.
                 */
                'answer' => "We deliver anywhere in the Philippines.\n\n"
                    . "That is the whole of it. We do not ship outside the country, so an address abroad "
                    . "cannot be served.\n\n"
                    . "Nothing is added to your total for delivery: the price at checkout is what the order comes to. "
                    . "Our staff confirm the arrangements with you once the order is placed, and you can follow its "
                    . "progress from your orders page.",
                'is_suggested' => true,
                'sort_order' => 160,
            ],
            [
                // Not a placeholder, because this one is not the business's to
                // fill in. No screen in this system records an expected date,
                // and once an order is collected the schedule belongs to the
                // courier, so any figure typed here would be invented.
                'intent_key' => 'delivery_timing',
                'category' => 'delivery',
                'label' => 'How long does delivery take?',
                // "delivered", "arrive" and friends are shared with
                // order_status, which wins them back through the possessive
                // bonus whenever someone says "my order". Without a
                // possessive the question is general, and a general question
                // belongs here rather than behind a sign-in prompt.
                // "take" and "takes" are gone: "what oil does a Ferrari
                // take" is the most natural way to ask the one question
                // this assistant exists for, and it was being answered
                // with the delivery policy. "How long" carries this
                // question on its own.
                'keywords' => 'long days day duration eta soon fast quick timeframe wait hours week weeks '
                    . 'delivery deliver delivered shipping arrive arrives tagal katagal kailan',
                'answer' => "We cannot give you a delivery time, and we would rather say so than invent one.\n\n"
                    . "Once an order leaves us it is with the courier, and how soon it reaches you is theirs to "
                    . "decide rather than ours. Nothing on this site quotes a delivery date for that reason.\n\n"
                    . "Our staff will confirm the arrangements with you after your order is placed, and you can "
                    . "follow its progress from your orders page.",
                'is_suggested' => false,
                'sort_order' => 165,
            ],
            [
                'intent_key' => 'returns_policy',
                'category' => 'delivery',
                'label' => 'What is your returns policy?',
                'keywords' => 'return returns refund exchange wrong damaged faulty leaking replace policy warranty',
                // The business's own wording, the same as the Returns page.
                // Deliberately not a policy window: returns here are agreed
                // per transaction, and inventing a "7 days" would be a promise
                // nobody made.
                'answer' => "Returns are arranged directly with your Sales Executive, because what can be done "
                    . "depends on the product and the terms of the order.

"
                    . "Three things may be possible:

"
                    . "- Replacement, with another product, subject to evaluation and availability
"
                    . "- Pull-out, where the product is collected, depending on what is agreed
"
                    . "- Refund, though these are generally not available on used product or completed bulk orders

"
                    . "Have your order number to hand and speak to your assigned Sales Executive. "
                    . "The Returns and Refunds page sets all of this out in full.",
                'is_suggested' => false,
                'sort_order' => 170,
            ],
            [
                'intent_key' => 'business_hours',
                'category' => 'company',
                'label' => 'What are your opening hours?',
                'keywords' => 'hours open opening close closing time schedule weekend sunday saturday available contact reach phone email',
                'answer' => "We are open :business_hours.\n\n"
                    . "Email us at :business_email and we will come back to you. You can also reach us on Facebook, "
                    . "and the assistant here answers at any hour.\n\n"
                    . "Orders placed outside opening hours are picked up the next working day.",
                'is_suggested' => false,
                'sort_order' => 180,
            ],
            [
                // Added once the shop could actually do this. Before the
                // reset existed the honest answer was "ask an administrator",
                // which is not an answer anybody wants about their own
                // password.
                'intent_key' => 'forgot_password',
                'category' => 'company',
                'label' => 'I forgot my password',
                'keywords' => 'forgot forgotten password reset lost cannot sign in login locked out change my password nakalimutan',
                'answer' => "Use the Forgot password? link on the sign-in page. Put in the email address on your account and "
                    . "we will send a link for setting a new one.\n\n"
                    . "The link lasts an hour and works once. Nobody here can read your old password, so there is nothing "
                    . "for us to tell you -- setting a new one is the only way back in.\n\n"
                    . "If nothing arrives, check the spam folder and that the address is the one you registered with.",
                'is_suggested' => false,
                'sort_order' => 185,
            ],
            [
                'intent_key' => 'bulk_orders',
                'category' => 'company',
                'label' => 'Do you supply businesses?',
                'keywords' => 'bulk wholesale fleet business businesses supply supplier drum barrel quote discount trade large quantity 200 liters commercial workshop',
                'answer' => "Yes. Two things on the site already suit a workshop or a fleet:\n\n"
                    . "- 200 litre drums of our 15W-40 diesel oils, from Canroyal and from Solar.\n"
                    . "- A box of 6 on the smaller packs, which is ordered from the product page like any other size.\n\n"
                    . "For anything beyond that -- a standing order, a mixed pallet, or a quantity you would rather discuss "
                    . "than add to a basket -- email :business_email and a Sales Executive will work out a price with you.",
                'is_suggested' => false,
                'sort_order' => 190,
            ],
        ];
    }
}
