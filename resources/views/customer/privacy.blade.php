@extends('layouts.customer')

@section('title', 'Privacy Policy - RANEY LUBRICANTS TRADING')

@section('content')
    @php
        // Hard-coded rather than now(), because a policy that silently claims
        // to have been updated today every time it is opened tells the reader
        // nothing. Change this by hand when the wording changes.
        $updated = 'September 23, 2026';

        // Written from what the system actually stores and sends, not from a
        // template. If the data collected changes, this has to change with it.
        $sections = [
            [
                'title' => 'What we collect',
                'body'  => ['We only collect what an order needs:'],
                'list'  => [
                    'Your name and email address, and a password, which we store scrambled so that no one here can read it.',
                    'Your mobile number and delivery address, so an order can reach you.',
                    'Your orders, payments and delivery history.',
                    'Your GCash reference number and the screenshot you upload, so a payment can be matched to an order.',
                    'Messages you send to the assistant on this site.',
                    'A profile photo, only if you upload one.',
                ],
            ],
            [
                'title' => 'How we use it',
                'body'  => [
                    'To take and deliver your orders, to confirm payments, to send you order updates by email, and to answer you when you get in touch. We also use it to keep our own sales records, which a business is required to keep.',
                    'We do not sell your personal information, and we do not use it to advertise to you anywhere else.',
                ],
            ],
            [
                'title' => 'Who else sees it',
                'body'  => ['Our staff, and only where their work requires it. Beyond that:'],
                'list'  => [
                    'Google, if you choose to sign in with Google. We receive your name, email address and a Google account identifier. We never receive your Google password.',
                    'Our email provider, which delivers the order emails we send you.',
                    'A delivery rider or courier, who is given your name, address and mobile number so they can find you.',
                ],
            ],
            [
                'title' => 'Cookies',
                'body'  => [
                    'We use one cookie, and it is the one that keeps you signed in and keeps your cart attached to you as you move between pages. Without it the site cannot tell one visitor from another.',
                    'We do not use advertising cookies, and there are no analytics or tracking scripts on this site.',
                ],
            ],
            [
                'title' => 'How long we keep it',
                'body'  => [
                    'Your account details are kept while your account is open. Order and payment records are kept after that, because they are business and tax records and we are required to be able to produce them.',
                    'Chat messages are kept so we can follow up on a question you asked earlier.',
                ],
            ],
            [
                'title' => 'Your rights',
                'body'  => ['Under the Data Privacy Act of 2012 (Republic Act No. 10173) you may:'],
                'list'  => [
                    'Ask what personal information we hold about you.',
                    'Correct anything that is wrong. Your name, email, mobile number and address can be edited yourself from your account page.',
                    'Ask us to delete your information, though we may have to keep order records we are required to keep.',
                    'Object to how we are using it, or withdraw a consent you gave.',
                    'Complain to the National Privacy Commission if you believe we have mishandled it.',
                ],
                'link'  => ['href' => '/profile', 'label' => 'Review your details'],
            ],
            [
                'title' => 'Keeping it safe',
                'body'  => [
                    'Passwords are stored hashed, never as readable text. Access to customer records is limited to staff accounts, and staff accounts can be deactivated. Payment screenshots are stored so that only signed-in staff and the customer who uploaded them can open them.',
                    'No system is perfectly secure. If something happens to your information that puts you at risk, we will tell you and the National Privacy Commission.',
                ],
            ],
            [
                'title' => 'Changes, and getting in touch',
                'body'  => [
                    'If we change this policy we will change the date at the top of this page. For any question about your information, or to make any of the requests above, contact us using the details below.',
                ],
            ],
        ];
    @endphp

    @include('partials.legal-page', [
        'eyebrow'  => 'Privacy Policy',
        'heading'  => 'What we collect, and what we do with it.',
        'lede'     => 'This policy covers the information RANEY LUBRICANTS TRADING holds about you when you shop with us. It is written to be read, so it is short.',
        'updated'  => $updated,
        'sections' => $sections,
    ])
@endsection
