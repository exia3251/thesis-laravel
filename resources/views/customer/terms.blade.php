@extends('layouts.customer')

@section('title', 'Terms of Service - RANEY LUBRICANTS TRADING')

@section('content')
    @php
        // See the note in privacy.blade.php: changed by hand, not by now().
        $updated = 'September 23, 2026';

        $sections = [
            [
                'title' => 'These terms',
                'body'  => [
                    'These terms apply when you use this site or place an order with RANEY LUBRICANTS TRADING. By placing an order you accept them.',
                    'We may change them. The date at the top of this page tells you when they last changed, and the terms that apply to an order are the ones in force on the day you placed it.',
                ],
            ],
            [
                'title' => 'Your account',
                'body'  => [
                    'You need an account to order, and the details on it must be your own and kept accurate. A wrong address or mobile number is the usual reason an order does not arrive.',
                    'Keep your password to yourself. Anything done through your account is treated as done by you. Tell us straight away if you think someone else has got into it.',
                    'We may suspend an account that is used to place false orders, to abuse our staff, or in a way that breaks these terms or the law.',
                ],
            ],
            [
                'title' => 'Placing an order',
                'body'  => [
                    'An order you place is an offer to buy, not a completed sale. The sale is made when we confirm the order and reserve the stock for you.',
                    'We may decline an order or cancel one we have confirmed, and will tell you why. The usual reasons are that the stock has run out, that the price or product details were listed wrongly, or that we cannot deliver to the address given.',
                ],
            ],
            [
                'title' => 'Prices and payment',
                'body'  => [
                    'All prices are in Philippine pesos. Prices can change, but a change never affects an order we have already confirmed.',
                    'You can pay by GCash, by cash on delivery, or by part of each. A GCash payment is checked by our staff against the reference number and the screenshot you send, which is why it is approved or rejected rather than being confirmed instantly. We will email you either way.',
                    'Until a payment is approved, the amount is still outstanding on your order.',
                ],
            ],
            [
                'title' => 'Delivery',
                'body'  => [
                    'We deliver to the address on your order. Delivery dates we give are estimates, not guarantees, and things outside our control such as weather and traffic can move them.',
                    'Please check your items on arrival. Once you confirm receipt, the order is recorded as delivered and the goods are your responsibility.',
                ],
            ],
            [
                'title' => 'Returns',
                'body'  => [
                    'Returns are arranged with your Sales Executive rather than through a fixed policy window, and what can be arranged depends on the product and the order. A refund is not the usual outcome; a replacement or a pull-out normally is.',
                    'The Returns and Refunds page sets out the three arrangements in full.',
                ],
                'link'  => ['href' => '/returns', 'label' => 'Read the returns arrangements'],
            ],
            [
                'title' => 'Product information and fitment',
                'body'  => [
                    'We describe our products as accurately as we can, and the standards they are certified to are stated on each listing and on our About page. Packaging and labelling may differ slightly from the photographs.',
                    'Any oil we suggest for a vehicle, whether on a product page or through the assistant on this site, is guidance and not a specification. Always check your owner\'s manual for the grade and standard your engine requires, and ask us if you are unsure. We are not responsible for damage caused by using an oil that does not meet the manufacturer\'s requirement for your engine.',
                ],
            ],
            [
                'title' => 'Using this site',
                'body'  => [
                    'Use the site for shopping with us and nothing else. Do not try to break into it, disrupt it, copy it wholesale, or collect data from it automatically.',
                    'The name, logo, text and images on this site belong to us or to the brands we distribute, and may not be reused without permission.',
                ],
            ],
            [
                'title' => 'Our responsibility',
                'body'  => [
                    'If we get something wrong with your order, we will put it right: by replacing the goods, arranging a pull-out, or refunding what you paid where a refund applies.',
                    'Beyond that we are not liable for indirect losses, such as lost income or lost time. Nothing here removes any right you have under Philippine law, including the Consumer Act, which no contract can sign away.',
                ],
            ],
            [
                'title' => 'Which law applies',
                'body'  => [
                    'These terms are governed by the laws of the Republic of the Philippines, and any dispute is for the Philippine courts.',
                    'If one part of these terms turns out to be unenforceable, the rest still stands.',
                ],
            ],
        ];
    @endphp

    @include('partials.legal-page', [
        'eyebrow'  => 'Terms of Service',
        'heading'  => 'The terms you are buying under.',
        'lede'     => 'These are the terms between you and RANEY LUBRICANTS TRADING when you order from this site. They are kept short on purpose.',
        'updated'  => $updated,
        'sections' => $sections,
    ])
@endsection
