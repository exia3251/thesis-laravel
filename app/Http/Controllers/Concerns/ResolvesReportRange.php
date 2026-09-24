<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The spans a report can be read over, and how finely to cut them up.
 *
 * Shared by the dashboard and the analytics screen so the two cannot drift
 * apart: "last 6 months" has to mean the same window on both, or the same
 * trade appears to have two different sizes depending on which page is open.
 */
trait ResolvesReportRange
{
    /**
     * Month-length spans start at the first of the month, so the bars are
     * whole months rather than a rolling window cut through the middle of
     * two of them. Day-length spans end today, because that is the question
     * being asked of them.
     */
    private const RANGES = [
        'all' => ['label' => 'All time'],
        '12m' => ['label' => 'Last 12 months', 'months' => 12],
        '6m' => ['label' => 'Last 6 months', 'months' => 6],
        '3m' => ['label' => 'Last 3 months', 'months' => 3],
        '30d' => ['label' => 'Last 30 days', 'days' => 30],
        '7d' => ['label' => 'Last 7 days', 'days' => 7],
        'custom' => ['label' => 'Custom range'],
    ];

    protected function rangeRules(): array
    {
        return [
            'range' => ['nullable', Rule::in(array_keys(self::RANGES))],
            'from' => ['required_if:range,custom', 'nullable', 'date'],
            'to' => ['required_if:range,custom', 'nullable', 'date', 'after_or_equal:from'],
        ];
    }

    protected function rangeMessages(): array
    {
        return [
            'from.required_if' => 'Choose a start date.',
            'to.required_if' => 'Choose an end date.',
            'to.after_or_equal' => 'The end date cannot come before the start date.',
        ];
    }

    /**
     * The window being asked for, and the one immediately before it.
     *
     * "All time" has nothing before it, so it carries no previous window and
     * the figures drop their comparison rather than measure against zero.
     */
    protected function resolveRange(Request $request, string $fallback = '30d'): array
    {
        $key = $request->get('range', $fallback);

        if (! isset(self::RANGES[$key])) {
            $key = $fallback;
        }

        $to = now()->endOfDay();

        if ($key === 'custom') {
            $from = Carbon::parse($request->get('from'))->startOfDay();
            $to = Carbon::parse($request->get('to'))->endOfDay();

            // Nothing is recorded ahead of now, so a window running past
            // today would only pad the chart with empty buckets.
            if ($to->isFuture()) {
                $to = now()->endOfDay();
            }

            $length = $this->daySpan($from, $to);
            $previousTo = $from->copy()->subSecond();

            return $this->rangeShape($key, $from, $to, $previousTo->copy()->subDays($length - 1)->startOfDay(), $previousTo);
        }

        if ($key === 'all') {
            $earliest = Sale::min('sale_date');
            $from = $earliest ? Carbon::parse($earliest)->startOfDay() : now()->startOfDay();

            return $this->rangeShape($key, $from, $to, null, null);
        }

        $spec = self::RANGES[$key];

        if (isset($spec['months'])) {
            $from = now()->subMonthsNoOverflow($spec['months'] - 1)->startOfMonth();
            $previousFrom = $from->copy()->subMonthsNoOverflow($spec['months']);
        } else {
            $from = now()->subDays($spec['days'] - 1)->startOfDay();
            $previousFrom = $from->copy()->subDays($spec['days']);
        }

        return $this->rangeShape($key, $from, $to, $previousFrom, $from->copy()->subSecond());
    }

    private function rangeShape(string $key, Carbon $from, Carbon $to, ?Carbon $previousFrom, ?Carbon $previousTo): array
    {
        return [
            'key' => $key,
            'label' => self::RANGES[$key]['label'],
            'from' => $from,
            'to' => $to,
            'previous_from' => $previousFrom,
            'previous_to' => $previousTo,
            'bucket' => $this->bucketFor($from, $to),
        ];
    }

    /** What the front end needs to caption a chart drawn from this window. */
    protected function rangePayload(array $range): array
    {
        return [
            'key' => $range['key'],
            'label' => $range['label'],
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
            'bucket' => $range['bucket'],
            'description' => $this->describe($range),
        ];
    }

    /**
     * How finely to cut the window up.
     *
     * A month of trade reads by day; a year of it does not, and three years
     * of months is a wall of bars nobody can count.
     */
    protected function bucketFor(Carbon $from, Carbon $to): string
    {
        $days = $this->daySpan($from, $to);

        if ($days <= 31) {
            return 'day';
        }

        return $days <= 1100 ? 'month' : 'year';
    }

    /**
     * Whole days between two moments, counted inclusively.
     *
     * These windows run from the start of one day to the end of another, and
     * Carbon measures the difference in fractions of a day, so a calendar
     * month came back as 31.99 days and was cut by month instead of by day.
     */
    protected function daySpan(Carbon $from, Carbon $to): int
    {
        return (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
    }

    protected function describe(array $range): string
    {
        $by = ['day' => 'by day', 'month' => 'by month', 'year' => 'by year'][$range['bucket']];

        return $range['from']->format('j M Y') . ' to ' . $range['to']->format('j M Y') . ', ' . $by;
    }

    /** The format MySQL should group a date column by, for this bucket. */
    protected function bucketSqlFormat(string $bucket): string
    {
        return ['day' => '%Y-%m-%d', 'month' => '%Y-%m', 'year' => '%Y'][$bucket];
    }

    /**
     * Every bucket in the window, in order, including the empty ones.
     *
     * Filling the gaps here rather than leaving them out means a quiet month
     * shows as a gap in the trade instead of vanishing from the chart and
     * shifting everything after it along one place.
     */
    protected function bucketPeriods(array $range): array
    {
        $shapes = [
            'day' => ['Y-m-d', 'j M', 'j F Y'],
            'month' => ['Y-m', 'M', 'F Y'],
            'year' => ['Y', 'Y', 'Y'],
        ];

        [$keyFormat, $shortFormat, $longFormat] = $shapes[$range['bucket']];

        $cursor = match ($range['bucket']) {
            'day' => $range['from']->copy()->startOfDay(),
            'month' => $range['from']->copy()->startOfMonth(),
            'year' => $range['from']->copy()->startOfYear(),
        };

        $periods = [];

        // Guarded against a window so long it would be unreadable anyway; the
        // bucket widens before this bites, so it is a backstop only.
        while ($cursor->lte($range['to']) && count($periods) < 400) {
            $periods[] = [
                'key' => $cursor->format($keyFormat),
                'label' => $cursor->format($shortFormat),
                'full_label' => $cursor->format($longFormat),
            ];

            match ($range['bucket']) {
                'day' => $cursor->addDay(),
                'month' => $cursor->addMonthNoOverflow(),
                'year' => $cursor->addYear(),
            };
        }

        return $periods;
    }
}
