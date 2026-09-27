<?php

namespace App\Services\Chatbot;

use App\Models\ChatConversation;
use App\Models\ChatIntent;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\VehicleSpec;
use Illuminate\Support\Collection;

/**
 * Turns a matched intent into an answer.
 *
 * Intents either carry fixed text or name a method here. The handlers read
 * the database at the moment the question is asked, so nothing in a reply is
 * a guess: a balance is the balance, a delivery state is the delivery state.
 *
 * Handler names come from the intent table, which an administrator can edit,
 * so they are resolved against an explicit allow-list rather than called by
 * name. Otherwise a typo in the back office would be a way to invoke
 * arbitrary methods on this class.
 */
class Responder
{
    private const HANDLERS = [
        'orderStatus', 'orderBalance', 'paymentState', 'orderCancel', 'orderList',
        'productFinder', 'productStock', 'productBrands', 'vehicleOil',
    ];

    /**
     * Said on every oil recommendation, without exception and without degree.
     *
     * Kept in config/business.php so the written answers, the vehicle guide
     * and the model all end on the same sentence. There used to be two of
     * these: a mild one for a car in the shop's own guide, a sterner one for
     * a car outside it. Read together they said the guide had been checked
     * and everything else was a guess. The guide has not been checked either.
     */
    private function handbookNote(): string
    {
        return (string) config('business.oil_disclaimer');
    }

    public function __construct(
        private readonly IntentMatcher $matcher,
        private readonly VehicleMatcher $vehicles,
    ) {
    }

    /**
     * @return array{body: string, payload: array, intent_key: ?string}
     */
    public function answer(ChatConversation $conversation, ChatIntent $intent, string $message, ?User $user): array
    {
        if ($intent->requires_login && !$user) {
            return $this->signInPrompt();
        }

        if (filled($intent->handler)) {
            if (!in_array($intent->handler, self::HANDLERS, true)) {
                return $this->reply("I know about that, but I cannot look it up right now.");
            }

            return $this->{$intent->handler}($conversation, $message, $user);
        }

        return $this->reply($this->fillPlaceholders((string) $intent->answer));
    }

    /** Nothing matched. Offer the closest questions rather than apologising twice. */
    public function fallback(Collection $suggestions, ?User $user): array
    {
        $body = $suggestions->isNotEmpty()
            ? "I am not sure about that one. Did you mean:"
            : "I did not understand that. I can help with orders, payments, delivery and finding the right oil.";

        return $this->reply($body, [
            'chips' => $this->chips($suggestions),
        ]);
    }

    /**
     * A grade the shelf does not hold, answered from the shelf.
     *
     * "Do you have 0W-16 for my hybrid" used to start the product finder,
     * which replied by asking what sort of product they were after -- which
     * they had just said. The catalogue knows the answer outright, so this
     * says it: no, and here is what there is instead.
     *
     * Deliberately not handed to the model. The right answer is a plain no
     * from the database, and the one thing a model might add here -- a
     * suggestion to use a grade the shop does carry instead -- is the one
     * thing that must never be said, because the wrong viscosity damages an
     * engine.
     *
     * @param  Collection<int,string>  $asked  the grades the message named
     */
    public function gradeNotCarried(Collection $asked, Collection $suggestions, ?User $user): array
    {
        $carried = Product::query()
            ->whereNotNull('viscosity_grade')
            ->where('viscosity_grade', '<>', '')
            ->distinct()
            ->pluck('viscosity_grade')
            ->map(fn ($grade) => $this->grade($grade))
            // Anything that is not a viscosity grade, ATF for one, is not an
            // answer to "which grades do you have".
            ->filter(fn (string $grade) => (bool) preg_match('/^\d+W-\d+$/i', $grade))
            ->unique()
            ->sortBy(fn (string $grade) => (int) $grade * 1000 + (int) substr($grade, strpos($grade, '-') + 1))
            ->values();

        $named = $asked->map(fn (string $grade) => $this->grade($grade))->unique();

        $body = 'We do not carry ' . $named->join(', ', ' or ') . '. ';

        $body .= $carried->isEmpty()
            ? 'Email ' . config('business.email') . ' and a Sales Executive can tell you what is available.'
            : 'The grades on the shelf are ' . $carried->join(', ', ' and ') . '. If none of them fits, email '
                . config('business.email') . ' and a Sales Executive can look for it.';

        $body .= "\n\n" . $this->handbookNote();

        return $this->reply($body, [
            'chips' => $this->chips($suggestions),
        ]);
    }

    public function greeting(?User $user): array
    {
        // A signed-out visitor is not offered order lookups, because they
        // would only reach the sign-in prompt. Offering what cannot be done
        // is how an assistant loses someone on its first line.
        $body = $user
            ? 'Hello ' . str($user->full_name)->before(' ')
                . '. I can check your orders, explain how payment works, or help you find the right oil.'
            : 'Hello. I can help you find the right oil, explain how payment and delivery work, '
                . 'or look up your orders once you are signed in.';

        return $this->reply($body, [
            'chips' => $this->chips($this->matcher->suggestions((bool) $user)),
        ]);
    }

    // ------------------------------------------------------------- orders

    private function orderStatus(ChatConversation $conversation, string $message, User $user): array
    {
        $order = $this->latestOrder($user);

        if (!$order) {
            return $this->reply("You have not placed an order yet.", [
                'link' => ['label' => 'Browse the shop', 'url' => '/shop'],
            ]);
        }

        $reference = $order->receipt_no ?: ('#' . $order->sale_id);

        if ($order->isCancelled()) {
            $body = "Order {$reference} was cancelled."
                . ($order->owesRefund() ? ' A refund is owed on it and our staff are arranging it.' : '');

            return $this->reply($body, $this->orderExtras($order, $user));
        }

        $body = match (true) {
            $order->isDelivered() => "Order {$reference} has been delivered.",
            $order->payment_status === 'paid' => "Order {$reference} is paid and out for delivery.",
            default => "Order {$reference} is being prepared. It leaves once payment is settled or arranged for delivery.",
        };

        $body .= "\n\nPlaced " . $order->sale_date?->format('j M Y')
            . ' · ' . $order->planLabel()
            . ' · ' . $this->money($order->total_amount);

        return $this->reply($body, $this->orderExtras($order, $user));
    }

    private function orderBalance(ChatConversation $conversation, string $message, User $user): array
    {
        $outstanding = Sale::where('user_id', $user->user_id)
            ->where('order_status', Sale::STATUS_ACTIVE)
            ->where('balance_due', '>', 0)
            ->orderByDesc('sale_id')
            ->get();

        if ($outstanding->isEmpty()) {
            return $this->reply("You have nothing outstanding. Every order of yours is settled.");
        }

        $total = (float) $outstanding->sum('balance_due');
        $order = $outstanding->first();
        $reference = $order->receipt_no ?: ('#' . $order->sale_id);

        if ($outstanding->count() === 1) {
            $body = "Order {$reference} has a balance of " . $this->money($order->balance_due) . ".";
        } else {
            $body = "You have {$outstanding->count()} orders with a balance, " . $this->money($total) . " in total."
                . "\n\nThe most recent, {$reference}, owes " . $this->money($order->balance_due) . ".";
        }

        // Split orders are the ones people actually ask about, because the
        // money is in two places and only one half is payable online.
        if ($order->payment_plan === Sale::PLAN_SPLIT) {
            $body .= "\n\n" . $this->money($order->paid_amount) . ' paid by GCash · '
                . $this->money($order->codAmount()) . ' due in cash on delivery';

            if ($order->gcashOutstanding() > 0) {
                $body .= "\n" . $this->money($order->gcashOutstanding()) . ' of the GCash down payment is still to send.';
            }
        }

        return $this->reply($body, $this->orderExtras($order, $user));
    }

    private function paymentState(ChatConversation $conversation, string $message, User $user): array
    {
        $order = $this->latestOrder($user);

        if (!$order) {
            return $this->reply("You have not placed an order yet, so there is no payment to check.");
        }

        $request = $order->paymentRequests()->orderByDesc('created_at')->first();
        $reference = $order->receipt_no ?: ('#' . $order->sale_id);

        if (!$request) {
            return $this->reply(
                "No GCash payment has been submitted against order {$reference} yet."
                . ($order->balance_due > 0 ? "\n\nIts balance is " . $this->money($order->balance_due) . '.' : ''),
                $this->orderExtras($order, $user)
            );
        }

        $body = match ($request->status) {
            'processing' => "Your payment of " . $this->money($request->amount) . " on order {$reference} is with our staff for checking. "
                . "It is usually reviewed the same working day.",
            'approved' => "Your payment of " . $this->money($request->amount) . " on order {$reference} was approved."
                . ($order->balance_due > 0 ? "\n\n" . $this->money($order->balance_due) . ' is still outstanding.' : "\n\nThat order is fully settled."),
            'rejected' => "Your payment of " . $this->money($request->amount) . " on order {$reference} could not be verified."
                . (filled($request->admin_notes) ? "\n\nStaff note: " . $request->admin_notes : '')
                . "\n\nYou can submit it again from the order page.",
            default => "There is a payment on order {$reference} with the status \"{$request->status}\".",
        };

        return $this->reply($body, $this->orderExtras($order, $user));
    }

    private function orderCancel(ChatConversation $conversation, string $message, User $user): array
    {
        $order = $this->latestOrder($user);

        if (!$order) {
            return $this->reply("You have no orders to cancel.");
        }

        $reference = $order->receipt_no ?: ('#' . $order->sale_id);

        $body = match (true) {
            $order->isCancelled() => "Order {$reference} is already cancelled.",
            $order->isDelivered() => "Order {$reference} has already been delivered, so it cannot be cancelled. "
                . "If something is wrong with it, please contact us and we will sort it out.",
            $order->canBeCancelledByCustomer() => "Yes. Order {$reference} can still be cancelled from its order page, "
                . "and anything you have already paid is refunded."
                . "\n\nOnce an order has been handed over it is too late to cancel.",
            default => "Order {$reference} has a payment being reviewed at the moment, so it cannot be cancelled until "
                . "that is finished. Staff usually review payments the same working day.",
        };

        return $this->reply($body, $this->orderExtras($order, $user));
    }

    private function orderList(ChatConversation $conversation, string $message, User $user): array
    {
        $orders = Sale::where('user_id', $user->user_id)->orderByDesc('sale_id')->limit(5)->get();

        if ($orders->isEmpty()) {
            return $this->reply("You have not placed an order yet.", [
                'link' => ['label' => 'Browse the shop', 'url' => '/shop'],
            ]);
        }

        $lines = $orders->map(function (Sale $order) {
            $reference = $order->receipt_no ?: ('#' . $order->sale_id);

            return '• ' . $reference . ' · ' . $order->sale_date?->format('j M Y')
                . ' · ' . $this->money($order->total_amount)
                . ' · ' . $order->statusLabel();
        })->implode("\n");

        return $this->reply("Your most recent orders:\n\n" . $lines, [
            'link' => ['label' => 'Open my orders', 'url' => '/orders'],
        ]);
    }

    // ----------------------------------------------------------- products

    private function productStock(ChatConversation $conversation, string $message, ?User $user): array
    {
        $available = Product::with('inventory')
            ->get()
            ->filter(fn (Product $p) => ($p->inventory->quantity ?? 0) > 0);

        if ($available->isEmpty()) {
            return $this->reply("Everything is out of stock at the moment. Please check back shortly.");
        }

        /*
         * This handler now answers prices as well as stock, because a card
         * carries both and a sentence about either goes stale. Which means it
         * has to read the question: "how much is the Solar 5W30" was being
         * answered with "we have 39 products in stock, here are a few", which
         * is true and useless.
         */
        $asked = $this->narrowToWhatWasNamed($available, $message);

        /*
         * Named something, and there is none of it. Patrol is the live case:
         * three products, all at zero, so "presyo ng Patrol" narrowed to
         * nothing and fell through to a list of what we do have -- which reads
         * as though the question had not been read.
         */
        if ($asked->isEmpty() && $this->namesSomethingWeList($message)) {
            return $this->reply(
                'That one is out of stock at the moment. Here is what is on the shelf today.',
                [
                    'products' => $this->productCards($available->sortByDesc(fn ($p) => $p->inventory->quantity ?? 0)->take(3)),
                    'link' => ['label' => 'See the whole catalogue', 'url' => '/shop'],
                ]
            );
        }

        if ($asked->isNotEmpty() && $asked->count() < $available->count()) {
            return $this->reply(
                'Here is what we have. The price and the pack sizes are on the card.',
                [
                    'products' => $this->productCards($asked->sortBy('price')),
                    'link' => ['label' => 'See the whole catalogue', 'url' => '/shop'],
                ]
            );
        }

        return $this->reply(
            "We have {$available->count()} products in stock right now. Here are a few:",
            [
                'products' => $this->productCards($available->sortByDesc(fn ($p) => $p->inventory->quantity ?? 0)->take(3)),
                'link' => ['label' => 'See the whole catalogue', 'url' => '/shop'],
            ]
        );
    }

    /**
     * Whether the message names a brand or grade the shop lists at all.
     *
     * Asked of everything rather than of what is in stock, which is the whole
     * point: it tells "we have none of that today" apart from "I did not
     * follow the question", and those deserve different answers.
     */
    private function namesSomethingWeList(string $message): bool
    {
        $said = mb_strtolower($message);
        $saidPlain = str_replace('-', '', $said);

        return Product::query()
            ->select('brand', 'viscosity_grade')
            ->get()
            ->contains(function (Product $product) use ($said, $saidPlain) {
                $brand = mb_strtolower((string) $product->brand);
                $grade = str_replace('-', '', mb_strtolower((string) $product->viscosity_grade));

                return ($brand !== '' && str_contains($said, $brand))
                    || ($grade !== '' && preg_match('/(?<!\d)' . preg_quote($grade, '/') . '/', $saidPlain));
            });
    }

    /**
     * Narrows a list to the brand and grade the message actually named.
     *
     * In two passes rather than one, so "the Solar 5W30" means that oil and
     * not every Solar plus every 5W-30. Either half alone still narrows: a
     * brand on its own gives that brand, a grade on its own gives that grade.
     *
     * @param  Collection<int,Product>  $products
     * @return Collection<int,Product>
     */
    private function narrowToWhatWasNamed(Collection $products, string $message): Collection
    {
        $said = mb_strtolower($message);

        // Both sides stripped of the hyphen, because "5W-30" and "5W30" are
        // the same grade and the column and the customer disagree about it.
        $saidPlain = str_replace('-', '', $said);

        $brands = $products->pluck('brand')->filter()->unique()
            ->filter(fn (string $brand) => str_contains($said, mb_strtolower($brand)));

        if ($brands->isNotEmpty()) {
            $products = $products->whereIn('brand', $brands->all());
        }

        $grades = $products->pluck('viscosity_grade')->filter()->unique()
            ->filter(function (string $grade) use ($saidPlain) {
                // Not preceded by a digit, or 5W40 would be found inside
                // 15W40 and a question about one would answer with the other.
                return (bool) preg_match(
                    '/(?<!\d)' . preg_quote(str_replace('-', '', mb_strtolower($grade)), '/') . '/',
                    $saidPlain
                );
            });

        if ($grades->isNotEmpty()) {
            $products = $products->whereIn('viscosity_grade', $grades->all());
        }

        return $brands->isEmpty() && $grades->isEmpty() ? collect() : $products->values();
    }

    private function productBrands(ChatConversation $conversation, string $message, ?User $user): array
    {
        $brands = Product::query()
            ->selectRaw('brand, COUNT(*) AS lines')
            ->groupBy('brand')
            ->orderBy('brand')
            ->get();

        if ($brands->isEmpty()) {
            return $this->reply("There is nothing in the catalogue yet.");
        }

        $lines = $brands->map(fn ($row) => '• ' . $row->brand . ' — ' . $row->lines . ' ' . str('product')->plural($row->lines))->implode("\n");

        return $this->reply("We carry:\n\n" . $lines, [
            'link' => ['label' => 'Browse the shop', 'url' => '/shop'],
        ]);
    }

    /**
     * A short guided flow rather than free text, because the useful question
     * -- which oil -- has only a handful of correct answers and a visitor
     * should not have to guess the vocabulary.
     */
    private function productFinder(ChatConversation $conversation, string $message, ?User $user): array
    {
        $types = Product::query()->distinct()->orderBy('oil_type')->pluck('oil_type');

        $conversation->update(['context' => ['flow' => 'finder', 'step' => 'oil_type']]);

        return $this->reply(
            "Happy to help. What kind of product are you after?",
            ['chips' => $types->map(fn ($t) => ['label' => $t, 'value' => $t])->all()]
        );
    }

    /**
     * Continues the finder. Returns null when the message is not an answer to
     * the step being asked, so the caller can treat it as a fresh question
     * instead of forcing it into the flow.
     */
    public function continueFinder(ChatConversation $conversation, string $message): ?array
    {
        $context = $conversation->context ?? [];

        if (($context['flow'] ?? null) !== 'finder') {
            return null;
        }

        $step = $context['step'] ?? 'oil_type';
        $choice = trim($message);

        if ($step === 'oil_type') {
            $type = Product::query()->distinct()->pluck('oil_type')
                ->first(fn ($t) => strcasecmp($t, $choice) === 0);

            if (!$type) {
                return null;
            }

            $grades = Product::where('oil_type', $type)
                ->whereNotNull('viscosity_grade')->where('viscosity_grade', '<>', '')
                ->distinct()->orderBy('viscosity_grade')->pluck('viscosity_grade');

            // Coolants and the like carry no grade, so that step is skipped.
            if ($grades->isEmpty()) {
                return $this->finderResults($conversation, $type, null);
            }

            $conversation->update(['context' => ['flow' => 'finder', 'step' => 'viscosity', 'oil_type' => $type]]);

            return $this->reply(
                "Which grade do you need? Your engine's handbook states the right one.",
                ['chips' => $grades->map(fn ($g) => ['label' => $g, 'value' => $g])
                    ->push(['label' => 'Not sure', 'value' => 'Not sure'])->all()]
            );
        }

        if ($step === 'viscosity') {
            $type = $context['oil_type'] ?? null;

            if (strcasecmp($choice, 'Not sure') === 0) {
                return $this->finderResults($conversation, $type, null);
            }

            $grade = Product::where('oil_type', $type)->distinct()->pluck('viscosity_grade')
                ->filter()->first(fn ($g) => strcasecmp($g, $choice) === 0);

            if (!$grade) {
                return null;
            }

            return $this->finderResults($conversation, $type, $grade);
        }

        return null;
    }

    private function finderResults(ChatConversation $conversation, ?string $type, ?string $grade): array
    {
        $conversation->clearContext();

        $query = Product::with('inventory')->where('oil_type', $type);

        if ($grade) {
            $query->where('viscosity_grade', $grade);
        }

        $matches = $query->get();
        $inStock = $matches->filter(fn (Product $p) => ($p->inventory->quantity ?? 0) > 0);
        $described = trim($type . ' ' . ($grade ?? ''));

        if ($matches->isEmpty()) {
            return $this->reply("We do not carry any {$described} at the moment.", [
                'link' => ['label' => 'Browse the shop', 'url' => '/shop'],
            ]);
        }

        if ($inStock->isEmpty()) {
            return $this->reply(
                "We carry {$described}, but every size is out of stock just now. Please check back shortly.",
                ['link' => ['label' => 'Browse the shop', 'url' => '/shop']]
            );
        }

        $body = $inStock->count() === 1
            ? "One {$described} is in stock:"
            : "{$inStock->count()} {$described} products are in stock:";

        return $this->reply($body, [
            'products' => $this->productCards($inStock->sortBy('price')->take(4)),
            'chips' => [['label' => 'Start again', 'value' => 'Find the right oil', 'intent' => 'product_finder']],
        ]);
    }

    // ----------------------------------------------------------- vehicles

    /**
     * Answers "what oil does my car take".
     *
     * Reached either from the intent, or straight from the message when a
     * model name was recognised in it.
     */
    public function vehicleOil(ChatConversation $conversation, string $message, ?User $user): array
    {
        $found = $this->vehicles->findConfident($message);

        // The model is in the guide, the year is not. Saying what a different
        // generation takes would be a wrong grade wearing a right one's
        // clothes, so it says which years it does hold and stops there.
        if ($found['outside_years'] ?? false) {
            return $this->vehicleYearNotCovered($conversation, $found['specs'], $found['year']);
        }

        if ($found['matched'] === null) {
            // A make we simply do not cover is a different answer from "no
            // vehicle mentioned". Offering a Ferrari owner a list of makes
            // that does not include Ferrari reads as though the assistant
            // did not understand the question.
            $unsupported = $this->vehicles->unsupportedMake($message);

            if ($unsupported) {
                return $this->unsupportedVehicle($conversation, $unsupported);
            }

            return $this->askForMake($conversation);
        }

        return $this->presentSpecs($conversation, $found['specs'], $found['year']);
    }

    /**
     * Continues the make-then-model walk. Returns null when the message is
     * not an answer to the step being asked, so the caller can treat it as a
     * fresh question rather than forcing it into the flow.
     */
    public function continueVehicle(ChatConversation $conversation, string $message): ?array
    {
        $context = $conversation->context ?? [];

        if (($context['flow'] ?? null) !== 'vehicle') {
            return null;
        }

        $choice = trim($message);
        $step = $context['step'] ?? 'make';

        if ($step === 'make') {
            $make = $this->vehicles->makes()->first(fn (string $m) => strcasecmp($m, $choice) === 0);

            if (!$make) {
                return null;
            }

            $conversation->update(['context' => ['flow' => 'vehicle', 'step' => 'model', 'make' => $make]]);

            return $this->reply("Which {$make}?", [
                'chips' => $this->vehicles->modelsFor($make)
                    ->map(fn (string $model) => ['label' => $model, 'value' => $model])->all(),
            ]);
        }

        if ($step === 'model') {
            $make = $context['make'] ?? '';
            $model = $this->vehicles->modelsFor($make)->first(fn (string $m) => strcasecmp($m, $choice) === 0);

            if (!$model) {
                return null;
            }

            return $this->presentSpecs($conversation, $this->vehicles->specsFor($make, $model), null);
        }

        return null;
    }

    /**
     * A make the guide does not cover.
     *
     * It still ends somewhere useful: the grade is what decides whether we
     * can help, and a customer holding their handbook can read it off in a
     * moment. Better than a flat refusal, and it does not pretend to know a
     * vehicle we have no figures for.
     */
    /**
     * The model is held, but not for the year they gave.
     *
     * Reached only when the model could not answer either, so this is the
     * last word rather than the first. It names the years the guide does
     * cover, because that tells them whether the guide is nearly right or
     * nothing like it, and it refuses to name a grade rather than quoting one
     * from a generation that is not theirs.
     *
     * @param  Collection<int,VehicleSpec>  $specs
     */
    private function vehicleYearNotCovered(ChatConversation $conversation, Collection $specs, ?int $year): array
    {
        $conversation->clearContext();

        $first = $specs->first();
        $name = $first ? trim($first->make . ' ' . $first->model) : 'that model';

        $covered = $specs
            ->map(fn (VehicleSpec $spec) => $this->yearsCovered($spec))
            ->filter()
            ->unique()
            ->join(', ', ' and ');

        $body = $covered === ''
            ? "Our guide does not cover a {$year} {$name}."
            : "Our guide covers the {$name} {$covered}, and yours is a {$year}, so I do not have its grade here.";

        $body .= "\n\nI will not name a grade from a different generation. If your handbook names one, tell me "
            . 'which and I will check whether we stock it. Otherwise email ' . config('business.email')
            . ' and a Sales Executive can look it up.';

        $body .= "\n\n" . $this->handbookNote();

        return $this->reply($body, [
            'chips' => [
                ['label' => 'What oils do you sell?', 'value' => 'what oils do you sell'],
                ['label' => 'Find the right oil', 'value' => 'Find the right oil', 'intent' => 'product_finder'],
            ],
        ]);
    }

    /** "from 2014 onwards", "between 2010 and 2015", or nothing at all. */
    private function yearsCovered(VehicleSpec $spec): string
    {
        return match (true) {
            $spec->year_from && $spec->year_to => "between {$spec->year_from} and {$spec->year_to}",
            (bool) $spec->year_from => "from {$spec->year_from} onwards",
            (bool) $spec->year_to => "up to {$spec->year_to}",
            default => '',
        };
    }

    private function unsupportedVehicle(ChatConversation $conversation, string $make): array
    {
        $conversation->clearContext();

        $body = "We do not carry oil specifications for {$make}, so I cannot tell you what a {$make} takes."
            . "

Our guide covers " . $this->vehicles->makes()->join(', ', ' and ') . '.'
            . "

If your handbook names a grade, tell me which one and I will check whether we stock it.";

        return $this->reply($body, [
            'chips' => [
                ['label' => 'What oils do you sell?', 'value' => 'what oils do you sell'],
                ['label' => 'Talk to our staff', 'value' => 'how do i contact you'],
            ],
        ]);
    }

    private function askForMake(ChatConversation $conversation): array
    {
        $conversation->update(['context' => ['flow' => 'vehicle', 'step' => 'make']]);

        /*
         * "Which make is it?" is how the trade asks it, and a customer has to
         * work out that "make" means the badge on the bonnet. Asked for the
         * model and year instead, they answer the way they would answer a
         * mechanic -- and "2018 Toyota Vios" is understood, because a message
         * that is not one of the brands below falls straight through this
         * flow to the matcher, which reads the whole thing.
         */
        return $this->reply(
            "I can look that up. What model and year is the car? You can type it, like \"2018 Toyota Vios\", "
                . 'or pick the brand below.',
            ['chips' => $this->vehicles->makes()->map(fn (string $make) => ['label' => $make, 'value' => $make])->all()]
        );
    }

    /**
     * One spec is an answer. Several means the model spans generations that
     * take different oils, and guessing between them is exactly the mistake
     * worth avoiding, so the customer picks.
     */
    private function presentSpecs(ChatConversation $conversation, Collection $specs, ?int $year): array
    {
        if ($specs->isEmpty()) {
            return $this->askForMake($conversation);
        }

        if ($specs->count() > 1) {
            $conversation->clearContext();

            $first = $specs->first();

            return $this->reply(
                "The {$first->make} {$first->model} comes in versions that take different oils. Which is yours?",
                [
                    'chips' => $specs->map(fn (VehicleSpec $spec) => [
                        'label' => trim(($spec->yearLabel() ? $spec->yearLabel() . ' - ' : '') . $spec->variant),
                        'value' => $spec->model . ' ' . ($spec->year_from ?: $spec->year_to ?: '') . ' ' . $spec->variant,
                    ])->all(),
                ]
            );
        }

        $conversation->clearContext();

        return $this->specAnswer($specs->first(), $year);
    }

    private function specAnswer(VehicleSpec $spec, ?int $year): array
    {
        $grade = $this->grade($spec->viscosity);
        $subject = trim(($year ? $year . ' ' : '') . $spec->make . ' ' . $spec->model);
        $variant = $spec->variant ? " ({$spec->variant})" : '';

        $stocked = $this->productsWithGrade($spec->viscosity);
        $body = ucfirst($this->article($subject)) . " {$subject}{$variant} takes {$grade}.";

        if ($spec->capacity_litres) {
            $body .= "\n\nAbout " . rtrim(rtrim(number_format((float) $spec->capacity_litres, 1), '0'), '.')
                . ' litres with a filter change' . $this->packAdvice((float) $spec->capacity_litres, $stocked) . '.';
        }

        $payload = [];

        if ($stocked->isNotEmpty()) {
            $payload['products'] = $this->productCards($stocked);
        } else {
            // Saying so is the point. Selling somebody the wrong grade
            // because it is what happens to be on the shelf is worse than
            // telling them we cannot help.
            $body .= "\n\nWe do not stock {$grade} at the moment.";

            $alternative = $spec->viscosity_alt ? $this->productsWithGrade($spec->viscosity_alt) : collect();

            if ($alternative->isNotEmpty()) {
                $body .= ' Some handbooks also permit ' . $this->grade($spec->viscosity_alt)
                    . ', which we do carry. Check yours before using it.';

                // Labelled, because on a narrow panel the sentence above
                // scrolls out of sight and three cards under "we do not
                // stock that" read as though they were the answer.
                $payload['products_note'] = 'Only if your handbook permits '
                    . $this->grade($spec->viscosity_alt) . ':';
                $payload['products'] = $this->productCards($alternative);
            }
        }

        if (filled($spec->notes)) {
            $body .= "\n\n" . $spec->notes;
        }

        // No sterner second sentence for an unverified row. Every row is
        // unverified, and saying so only on some of them told customers the
        // rest had been checked against a manual. None of them have.
        $body .= "\n\n" . $this->handbookNote();

        $payload['chips'] = [['label' => 'Look up another vehicle', 'value' => 'what oil for my car', 'intent' => 'vehicle_oil']];

        return $this->reply($body, $payload);
    }

    /** In stock, in this grade. */
    /**
     * Every pack of every oil we hold in a grade.
     *
     * Deliberately not reduced to one row per oil: how many litres a customer
     * needs is answered from the pack sizes, so that has to see all of them.
     * The reducing happens in productCards, where it belongs.
     */
    private function productsWithGrade(?string $viscosity): Collection
    {
        if (blank($viscosity)) {
            return collect();
        }

        return Product::with('inventory')
            ->where('viscosity_grade', $viscosity)
            ->get()
            ->filter(fn (Product $product) => ($product->inventory->quantity ?? 0) > 0)
            ->sortBy('price')
            ->values();
    }

    /**
     * Turns a capacity into the pack somebody should actually buy, using the
     * sizes carried in this grade rather than a generic answer.
     */
    private function packAdvice(float $litres, Collection $stocked): string
    {
        $sizes = $stocked
            ->map(fn (Product $product) => $this->litresIn($product->unit))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($sizes->isEmpty()) {
            return '';
        }

        /*
         * A pack that covers it without being absurd. The drum covers a
         * 6.7 litre sump too, and "a 200 litre pack covers it" is not advice
         * anybody asked for, so a single pack has to be within reach of what
         * is actually needed.
         */
        $single = $sizes->first(fn (float $size) => $size >= $litres && $size <= $litres * 2);

        if ($single) {
            return ', so a ' . $this->litreLabel($single) . ' pack covers it';
        }

        /*
         * Otherwise make it up out of the packs on the shelf, biggest first.
         * Counting only in the largest usable pack sold 2 x 5 litre for a
         * 5.5 litre sump, when a 5 and a 1 is six litres and less money.
         */
        $usable = $sizes->filter(fn (float $size) => $size <= $litres)->sortDesc()->values();

        if ($usable->isEmpty()) {
            return ', so a ' . $this->litreLabel($sizes->first()) . ' pack covers it';
        }

        $remaining = $litres;
        $take = [];

        foreach ($usable as $size) {
            $count = (int) floor($remaining / $size);

            if ($count > 0) {
                $take[] = [$count, $size];
                $remaining -= $count * $size;
            }
        }

        // Whatever is left over still has to be bought, so round it up into
        // the smallest pack that covers the remainder.
        if ($remaining > 0.01) {
            $topUp = $sizes->first(fn (float $size) => $size >= $remaining) ?? $usable->last();
            $take[] = [1, $topUp];
        }

        $parts = collect($take)
            ->groupBy(fn (array $row) => (string) $row[1])
            ->map(fn (Collection $rows) => [array_sum($rows->map(fn ($r) => $r[0])->all()), $rows->first()[1]])
            ->sortByDesc(fn (array $row) => $row[1])
            ->map(fn (array $row) => $row[0] . ' x ' . $this->litreLabel($row[1]))
            ->values();

        return ', so you would need ' . $parts->join(' and ');
    }

    /** "20 Liters" -> 20.0 */
    private function litresIn(?string $unit): ?float
    {
        return preg_match('/(\d+(?:\.\d+)?)/', (string) $unit, $m) ? (float) $m[1] : null;
    }

    private function litreLabel(float $litres): string
    {
        $rounded = rtrim(rtrim(number_format($litres, 1), '0'), '.');

        return $rounded . ' litre';
    }

    /** "an Isuzu", "a Toyota". Reads wrong otherwise, and it is one line. */
    private function article(string $subject): string
    {
        return str_contains('aeiou', mb_strtolower(mb_substr($subject, 0, 1))) ? 'an' : 'a';
    }

    /** "5W30" -> "5W-30", which is how a handbook prints it. */
    private function grade(?string $viscosity): string
    {
        return preg_replace('/^(\d+W)(\d+)$/i', '$1-$2', (string) $viscosity);
    }

    // ------------------------------------------------------------ helpers

    private function latestOrder(User $user): ?Sale
    {
        return Sale::where('user_id', $user->user_id)
            ->orderByRaw("CASE WHEN order_status = '" . Sale::STATUS_ACTIVE . "' THEN 0 ELSE 1 END")
            ->orderByDesc('sale_id')
            ->first();
    }

    /** The link and follow-up chips that belong with any answer about an order. */
    private function orderExtras(Sale $order, User $user): array
    {
        $extras = ['link' => ['label' => 'Open this order', 'url' => '/orders/' . $order->sale_id]];

        $others = Sale::where('user_id', $user->user_id)->count();

        if ($others > 1) {
            $extras['chips'] = [['label' => 'Show my recent orders', 'value' => 'Show my recent orders', 'intent' => 'order_list']];
        }

        return $extras;
    }

    /**
     * One card per oil, cheapest pack standing for the line.
     *
     * Products are a row per pack size, so three cards could be the same
     * Solar 5W30 twice over -- its 1L and its 4L -- under a name that does
     * not carry the size. Sizes are chosen on the product page anyway.
     */
    private function productCards(Collection $products): array
    {
        return $products
            ->sortBy('price')
            ->unique(fn (Product $product) => $product->product_line ?: 'product-' . $product->product_id)
            ->take(3)
            ->map(fn (Product $product) => [
            'product_id' => $product->product_id,
            'name' => $product->product_name,
            'brand' => $product->brand,
            'unit' => $product->unit,
            'price' => $this->money($product->price),
            'stock' => (int) ($product->inventory->quantity ?? 0),
            'url' => '/shop/products/' . $product->product_id,
            'image' => $product->image_path ? asset('storage/' . $product->image_path) : null,
        ])->values()->all();
    }

    private function chips(Collection $intents): array
    {
        return $intents->map(fn (ChatIntent $intent) => [
            'label' => $intent->label,
            'value' => $intent->label,
            'intent' => $intent->intent_key,
        ])->values()->all();
    }

    private function signInPrompt(): array
    {
        return $this->reply(
            "That one is about your own account, so you will need to sign in first. Once you have, ask me again and I can look it up.",
            ['link' => ['label' => 'Sign in', 'url' => '/shop/login']]
        );
    }

    /**
     * Fixed answers may quote the payment rules, which live in configuration.
     * Substituting at reply time means editing config cannot leave the
     * assistant quoting a figure the checkout no longer enforces.
     */
    private function fillPlaceholders(string $answer): string
    {
        return strtr($answer, [
            ':down_payment_percent' => (string) config('payments.minimum_down_payment_percent', 50),
            ':minimum_extra_payment' => number_format((float) config('payments.minimum_extra_payment', 500), 0),
            ':grace_days_min' => (string) config('payments.settlement_grace_days.minimum', 30),
            ':grace_days_max' => (string) config('payments.settlement_grace_days.maximum', 60),
            // Contact details belong in one place too, or the assistant ends
            // up quoting hours the footer has since changed.
            ':business_hours' => (string) config('business.hours'),
            ':business_email' => (string) config('business.email'),
            ':business_address' => (string) config('business.address'),
        ]);
    }

    private function money(float|string|null $amount): string
    {
        return 'PHP ' . number_format((float) $amount, 2);
    }

    private function reply(string $body, array $payload = []): array
    {
        return ['body' => $body, 'payload' => array_filter($payload)];
    }
}
