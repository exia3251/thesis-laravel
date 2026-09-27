<?php

namespace Tests\Feature;

use App\Models\VehicleSpec;
use App\Services\Chatbot\VehicleMatcher;
use Database\Seeders\VehicleSpecSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reading a vehicle out of what somebody typed.
 *
 * The dangerous failure is not missing a car, it is confidently naming the
 * wrong one, so most of this file is about what must NOT match.
 */
class VehicleOilLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VehicleSpecSeeder::class);
    }

    public static function questions(): array
    {
        return [
            'model alone'            => ['what oil for a vios', 'vios'],
            'model with year'        => ['what oil for my 2015 vios', 'vios'],
            'two word model'         => ['oil for a montero sport', 'montero sport'],
            'hyphenated model'       => ['cr-v oil', 'cr-v'],
            'run together'           => ['crv oil change', 'crv'],
            'alias'                  => ['dmax 2022', 'dmax'],
            'model with a letter'    => ['mirage g4', 'mirage g4'],
            'make and model'         => ['isuzu elf', 'elf'],
            'sentence around it'     => ['hi, i drive a 2019 hilux, what should i use', 'hilux'],
        ];
    }

    #[Test]
    #[DataProvider('questions')]
    public function it_reads_the_vehicle_out_of_a_question(string $question, string $expected): void
    {
        $result = (new VehicleMatcher)->findConfident($question);

        $this->assertSame($expected, $result['matched']);
        $this->assertNotEmpty($result['specs']);
    }

    public static function notVehicleQuestions(): array
    {
        return [
            // "City" is a Honda. It is also where people live.
            'a place'            => ['do you deliver to quezon city'],
            'a place again'      => ['do you deliver to my city'],
            // "Rush" and "Carry" are Toyotas and Suzukis, and ordinary verbs.
            'a verb'             => ['can you rush my order'],
            'another verb'       => ['do you carry gcash'],
            'ordinary question'  => ['how can i pay'],
            'about an order'     => ['can i cancel my order'],
            'nothing at all'     => ['asdkjh qwe'],
        ];
    }

    #[Test]
    #[DataProvider('notVehicleQuestions')]
    public function it_does_not_read_a_vehicle_into_an_ordinary_question(string $question): void
    {
        $result = (new VehicleMatcher)->findConfident($question);

        $this->assertNull(
            $result['matched'],
            "\"{$question}\" was wrongly read as a question about a {$result['matched']}."
        );
    }

    public static function uncoveredMakes(): array
    {
        return [
            'a supercar'        => ['what oil for my ferrari', 'Ferrari'],
            'a German saloon'   => ['oil for my bmw 320i', 'BMW'],
            'spelled out'       => ['what does a mercedes-benz take', 'Mercedes-Benz'],
            'a Japanese make'   => ['what oil does my subaru forester take', 'Subaru'],
            'asked in Filipino' => ['anong oil sa lexus ko', 'Lexus'],
            'sold here'         => ['oil for my chery tiggo', 'Chery'],
        ];
    }

    #[Test]
    #[DataProvider('uncoveredMakes')]
    public function it_names_a_make_it_does_not_cover(string $question, string $expected): void
    {
        // Offering a Ferrari owner a list of makes that does not contain
        // Ferrari reads as not having understood the question.
        $this->assertSame($expected, (new VehicleMatcher)->unsupportedMake($question));
    }

    #[Test]
    public function a_make_it_does_cover_is_never_called_uncovered(): void
    {
        $matcher = new VehicleMatcher;

        foreach ($matcher->makes() as $make) {
            $this->assertNull(
                $matcher->unsupportedMake("what oil for my {$make}"),
                "{$make} is in the guide but was reported as uncovered."
            );
        }
    }

    #[Test]
    public function an_ordinary_question_names_no_uncovered_make(): void
    {
        $matcher = new VehicleMatcher;

        foreach (['how can i pay', 'do you deliver to my city', 'can i cancel my order'] as $question) {
            $this->assertNull($matcher->unsupportedMake($question));
        }
    }

    #[Test]
    public function an_everyday_word_still_works_when_the_message_is_about_a_vehicle(): void
    {
        $result = (new VehicleMatcher)->findConfident('what oil for my city');

        $this->assertSame('city', $result['matched']);
    }

    #[Test]
    public function a_year_picks_the_right_generation(): void
    {
        $matcher = new VehicleMatcher;

        $old = $matcher->findConfident('what oil for my 2015 vios');
        $new = $matcher->findConfident('what oil for my 2021 vios');

        $this->assertCount(1, $old['specs']);
        $this->assertCount(1, $new['specs']);
        $this->assertNotSame(
            $old['specs']->first()->viscosity,
            $new['specs']->first()->viscosity,
            'Two generations that take different oils were given the same answer.'
        );
    }

    #[Test]
    public function a_model_without_a_year_returns_every_generation_to_choose_from(): void
    {
        $result = (new VehicleMatcher)->findConfident('oil for a montero sport');

        $this->assertGreaterThan(
            1,
            $result['specs']->count(),
            'A model spanning generations should offer the choice rather than pick one.'
        );
    }

    #[Test]
    public function the_longer_model_name_wins(): void
    {
        // Both "montero" and "montero sport" are stored spellings.
        $result = (new VehicleMatcher)->findConfident('oil for a montero sport 2018');

        $this->assertSame('montero sport', $result['matched']);
    }

    #[Test]
    public function a_row_only_claims_to_be_checked_if_it_says_who_checked_it(): void
    {
        // It used to be that no row could be verified at all. Some have been
        // now, one at a time and against the manufacturer's own figures, and
        // each of those carries a source saying so and when. What must never
        // happen is a row marked checked while still carrying the generic
        // "general reference" line, because that is a claim with nothing
        // behind it.
        $unsupported = VehicleSpec::where('is_verified', true)
            ->where(fn ($query) => $query->whereNull('source')->orWhere('source', 'like', 'General reference%'))
            ->get();

        $this->assertTrue(
            $unsupported->isEmpty(),
            'Marked as checked but with no source saying by what: '
                . $unsupported->map(fn (VehicleSpec $s) => trim("{$s->make} {$s->model}"))->implode(', ')
        );

        $this->assertSame(
            0,
            VehicleSpec::whereNull('source')->count(),
            'Every row must say where its figure came from.'
        );
    }

    #[Test]
    public function every_row_carries_a_usable_viscosity_grade(): void
    {
        $bad = VehicleSpec::get()->reject(
            fn (VehicleSpec $spec) => (bool) preg_match('/^\d{1,2}W\d{2}$/', $spec->viscosity)
        );

        $this->assertTrue(
            $bad->isEmpty(),
            'These rows hold a grade the catalogue could never match: ' . $bad->pluck('viscosity')->implode(', ')
        );
    }
}
