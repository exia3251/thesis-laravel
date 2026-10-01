<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The money scale down the left of the Collections chart.
 *
 * The chart drew bars against the tallest bar, so the tallest was always
 * full height and the only figure on screen was the one in the caption. A
 * quiet week and a record week drew the same picture. There is a scale now,
 * and a scale that is wrong is worse than none: somebody reads a height off
 * it and believes the number.
 *
 * The scale is written in JavaScript, so it is lifted out of the view and
 * run, rather than matched against as text. A test that only checked the
 * source said nothing about what the arithmetic produces.
 */
class CollectionsAxisTest extends TestCase
{
    /** Pulls the two functions out of the Blade file and runs them in node. */
    private function axis(array $peaks): array
    {
        $view = file_get_contents(resource_path('views/admin/dashboard.blade.php'));

        $source = '';

        foreach (['function moneyAxis(', 'function axisTick('] as $signature) {
            $start = strpos($view, $signature);
            $this->assertNotFalse($start, "{$signature} has been renamed or removed from the chart.");

            // Functions here are written at four-space indent, so their own
            // closing brace is the first one at that indent.
            $end = strpos($view, "\n    }", $start);
            $this->assertNotFalse($end, "Could not find the end of {$signature}");

            $source .= substr($view, $start, $end - $start) . "\n    }\n";
        }

        $script = $source . "\n"
            . 'const out = ' . json_encode($peaks) . '.map((p) => {'
            . '  const a = moneyAxis(p);'
            . '  return { peak: p, max: a.max, ticks: a.ticks, labels: a.ticks.map(axisTick) };'
            . '});'
            . 'console.log(JSON.stringify(out));';

        $file = tempnam(sys_get_temp_dir(), 'axis') . '.js';
        file_put_contents($file, $script);

        exec('node ' . escapeshellarg($file) . ' 2>&1', $output, $status);
        @unlink($file);

        $this->assertSame(0, $status, 'The axis code did not run: ' . implode("\n", $output));

        return json_decode(implode('', $output), true);
    }

    #[Test]
    public function the_scale_covers_the_tallest_bar_without_ever_cutting_it_off(): void
    {
        $peaks = [1, 7, 47, 100, 950, 1500, 12345, 56000, 99999, 265190, 3250000, 48_000_000];

        foreach ($this->axis($peaks) as $row) {
            $this->assertGreaterThanOrEqual(
                $row['peak'],
                $row['max'],
                "A peak of {$row['peak']} would be drawn above the top of the axis."
            );
        }
    }

    /**
     * The top of the axis is a whole number of steps, not the data itself.
     *
     * That is the fix: when the top was the tallest bar, the tallest bar was
     * full height every single time, so a quiet week and a record week drew
     * the same picture. A peak that already lands on a round step still
     * reaches the top -- but by coincidence rather than by rule, which is
     * the difference that matters.
     */
    #[Test]
    public function the_top_of_the_scale_is_a_round_step_rather_than_the_data(): void
    {
        $awkward = [265190, 12345, 47, 3250000, 56000, 99999];

        foreach ($this->axis($awkward) as $row) {
            $step = $row['ticks'][1] - $row['ticks'][0];

            $this->assertEqualsWithDelta(
                0,
                fmod((float) $row['max'], (float) $step),
                0.01,
                "The top of the axis for {$row['peak']} is not a whole number of steps."
            );

            // None of these peaks is itself a round step, so each must leave
            // its bar short of the top.
            $this->assertGreaterThan(
                $row['peak'],
                $row['max'],
                "The axis for {$row['peak']} stops exactly at the tallest bar."
            );
        }
    }

    #[Test]
    public function the_steps_are_even_and_start_at_zero(): void
    {
        foreach ($this->axis([1, 950, 12345, 56000, 265190, 3250000]) as $row) {
            $ticks = $row['ticks'];

            $this->assertSame(0, $ticks[0], 'The axis does not start at zero.');
            $this->assertSame($row['max'], end($ticks), 'The last mark is not the top of the axis.');
            $this->assertGreaterThanOrEqual(2, count($ticks), 'An axis needs more than one mark.');

            $step = $ticks[1] - $ticks[0];

            for ($i = 1; $i < count($ticks); $i++) {
                $this->assertEqualsWithDelta(
                    $step,
                    $ticks[$i] - $ticks[$i - 1],
                    0.01,
                    "The gap between marks changes partway up the axis for a peak of {$row['peak']}."
                );
            }
        }
    }

    /**
     * The point of rounding: marks somebody reads without working them out.
     */
    #[Test]
    public function the_marks_are_figures_a_reader_adds_up_without_thinking(): void
    {
        $expected = [
            56000 => ['0', '20k', '40k', '60k'],
            265190 => ['0', '100k', '200k', '300k'],
            12345 => ['0', '5k', '10k', '15k'],
            950 => ['0', '250', '500', '750', '1k'],
            3250000 => ['0', '1M', '2M', '3M', '4M'],
        ];

        $rows = $this->axis(array_keys($expected));

        foreach ($rows as $row) {
            $this->assertSame(
                $expected[$row['peak']],
                $row['labels'],
                "The axis for a peak of {$row['peak']} is not labelled in round figures."
            );
        }
    }

    #[Test]
    public function a_single_peso_does_not_produce_an_axis_of_fractions(): void
    {
        $rows = $this->axis([1, 3, 7]);

        foreach ($rows as $row) {
            foreach ($row['ticks'] as $tick) {
                $this->assertSame(
                    (float) $tick,
                    floor((float) $tick),
                    "A peak of {$row['peak']} put a fraction of a peso on the axis."
                );
            }
        }
    }

    #[Test]
    public function the_chart_draws_the_scale_and_its_gridlines(): void
    {
        $view = file_get_contents(resource_path('views/admin/dashboard.blade.php'));

        // Bars are measured against the axis, not against the tallest bar --
        // otherwise the heights and the figures beside them disagree.
        $this->assertStringContainsString('columns(series, moneyAxis(peak), busiest)', $view);
        $this->assertStringContainsString('const peak = axis.max;', $view);

        // A mark and its gridline are placed by the same measurement.
        $this->assertSame(
            2,
            substr_count($view, 'style="bottom:${(value / peak) * 100}%"'),
            'The gridline and its figure are no longer positioned identically.'
        );
    }
}
