<?php

namespace App\Services\Chatbot;

use App\Models\VehicleSpec;
use Illuminate\Support\Collection;

/**
 * Pulls a vehicle out of something a customer typed.
 *
 * "what oil for my 2015 vios" has to become a model and a year. Model names
 * are matched as phrases rather than words, because several are two words
 * ("Montero Sport", "Corolla Altis") and matching only the first would send a
 * Montero Sport owner to the wrong generation.
 */
class VehicleMatcher
{
    /** Older than this and it is a quantity, not a model year. */
    private const EARLIEST_YEAR = 1980;

    /**
     * Model names that are also ordinary words. "Do you deliver to my city"
     * must not be read as a Honda City, so these are only accepted when the
     * message also carries vehicle context.
     */
    private const AMBIGUOUS = [
        'city', 'rush', 'carry', 'accent', 'swift', 'terra', 'jazz', 'brio',
        'ranger', 'elf', 'territory', 'adventure', '3', 'zs', 'g4',
    ];

    /** Words that make a message a question about a vehicle. */
    private const CONTEXT = [
        'oil', 'oils', 'car', 'cars', 'vehicle', 'engine', 'motor', 'truck',
        'pickup', 'van', 'suv', 'diesel', 'gasoline', 'petrol', 'grade',
        'viscosity', 'drive', 'driving', 'drives', 'manual', 'change',
        'sasakyan', 'kotse', 'makina', 'lubricant', 'recommend', 'use', 'uses',
    ];

    /**
     * Makes a customer here might plausibly type that the guide does not
     * cover, so "what oil for my Ferrari" can be answered with "we do not
     * have specifications for Ferrari" rather than with "which make is it?"
     * and a list that does not contain it.
     *
     * Kept as a list rather than guessed at, because the alternative is
     * treating any unrecognised word as a car and telling somebody asking
     * about delivery that we do not stock oil for their "tomorrow".
     */
    private const UNSUPPORTED_MAKES = [
        // European
        'bmw' => 'BMW', 'mercedes' => 'Mercedes-Benz', 'mercedes-benz' => 'Mercedes-Benz',
        'benz' => 'Mercedes-Benz', 'audi' => 'Audi', 'volkswagen' => 'Volkswagen',
        'vw' => 'Volkswagen', 'porsche' => 'Porsche', 'ferrari' => 'Ferrari',
        'lamborghini' => 'Lamborghini', 'maserati' => 'Maserati', 'bentley' => 'Bentley',
        'jaguar' => 'Jaguar', 'volvo' => 'Volvo', 'peugeot' => 'Peugeot',
        'renault' => 'Renault', 'citroen' => 'Citroen', 'skoda' => 'Skoda',
        'fiat' => 'Fiat', 'mini' => 'MINI', 'opel' => 'Opel',

        // Japanese and Korean we do not hold specifications for
        'subaru' => 'Subaru', 'lexus' => 'Lexus', 'infiniti' => 'Infiniti',
        'acura' => 'Acura', 'daihatsu' => 'Daihatsu', 'genesis' => 'Genesis',
        'ssangyong' => 'SsangYong',

        // American
        'chevrolet' => 'Chevrolet', 'chevy' => 'Chevrolet', 'jeep' => 'Jeep',
        'dodge' => 'Dodge', 'chrysler' => 'Chrysler', 'cadillac' => 'Cadillac',
        'gmc' => 'GMC', 'tesla' => 'Tesla', 'lincoln' => 'Lincoln',

        // Chinese and Indian marques sold here
        'byd' => 'BYD', 'chery' => 'Chery', 'haval' => 'Haval', 'gwm' => 'GWM',
        'foton' => 'Foton', 'jac' => 'JAC', 'maxus' => 'Maxus',
        'changan' => 'Changan', 'dongfeng' => 'Dongfeng', 'baic' => 'BAIC',
        'mahindra' => 'Mahindra', 'tata' => 'Tata',
    ];

    private ?Collection $specs = null;

    /**
     * @return array{specs: Collection, year: ?int, matched: ?string}
     */
    public function find(string $message): array
    {
        $text = $this->normalise($message);
        $year = $this->extractYear($text);
        $name = $this->longestModelMatch($text);

        if ($name === null) {
            return ['specs' => collect(), 'year' => $year, 'matched' => null];
        }

        $specs = $this->all()->filter(fn (VehicleSpec $spec) => in_array($name, $spec->searchNames(), true));

        // A year narrows a model down to one generation. If it matches none,
        // the year is probably wrong or the car is outside what we hold, so
        // every generation is offered rather than nothing.
        if ($year !== null) {
            $inRange = $specs->filter(fn (VehicleSpec $spec) => $spec->coversYear($year));

            if ($inRange->isNotEmpty()) {
                $specs = $inRange;
            }
        }

        return ['specs' => $specs->values(), 'year' => $year, 'matched' => $name];
    }

    /**
     * As find(), but returns nothing unless the match is safe to act on.
     *
     * Called before intent matching, so a false positive here would hijack an
     * ordinary question. A distinctive name like "Montero Sport" stands on
     * its own; an everyday word like "City" needs the message to be about a
     * vehicle before it counts.
     */
    public function findConfident(string $message): array
    {
        $result = $this->find($message);

        if ($result['matched'] === null) {
            return $result;
        }

        if (!in_array($result['matched'], self::AMBIGUOUS, true)) {
            return $result;
        }

        $text = $this->normalise($message);
        $tokens = explode(' ', $text);

        $hasContext = $result['year'] !== null
            || array_intersect(self::CONTEXT, $tokens)
            || $this->makes()->contains(fn (string $make) => in_array(mb_strtolower($make), $tokens, true));

        return $hasContext
            ? $result
            : ['specs' => collect(), 'year' => null, 'matched' => null];
    }

    /**
     * The make a customer named that the guide does not cover, if any.
     *
     * Only consulted once a lookup has come back empty, so a make we do hold
     * is never mistaken for one we do not.
     */
    public function unsupportedMake(string $message): ?string
    {
        $tokens = explode(' ', $this->normalise($message));

        foreach ($tokens as $token) {
            if (isset(self::UNSUPPORTED_MAKES[$token])) {
                return self::UNSUPPORTED_MAKES[$token];
            }
        }

        return null;
    }

    /**
     * Whether the message names a car make at all, stocked for or not.
     *
     * The signal that a question is about a vehicle, separate from whether
     * this system can answer it. "What oil does a Ferrari take" and "what oil
     * does a Wigo take" both mention one; neither is in the specs table; and
     * both were being answered by the generic product finder, which asks what
     * kind of product you are after.
     */
    public function mentionsAnyMake(string $message): bool
    {
        if ($this->unsupportedMake($message) !== null) {
            return true;
        }

        $tokens = explode(' ', $this->normalise($message));
        $known = $this->makes()->map(fn (string $make) => $this->normalise($make))->all();

        foreach ($tokens as $token) {
            if ($token !== '' && in_array($token, $known, true)) {
                return true;
            }
        }

        return false;
    }

    /** Every make held, for the guided flow. */
    public function makes(): Collection
    {
        return $this->all()->pluck('make')->unique()->sort()->values();
    }

    /** @return Collection<int,string> */
    public function modelsFor(string $make): Collection
    {
        return $this->all()
            ->filter(fn (VehicleSpec $spec) => strcasecmp($spec->make, $make) === 0)
            ->pluck('model')
            ->unique()
            ->sort()
            ->values();
    }

    public function specsFor(string $make, string $model): Collection
    {
        return $this->all()
            ->filter(fn (VehicleSpec $spec) => strcasecmp($spec->make, $make) === 0
                && strcasecmp($spec->model, $model) === 0)
            ->values();
    }

    private function all(): Collection
    {
        return $this->specs ??= VehicleSpec::orderBy('make')->orderBy('model')->orderByDesc('year_from')->get();
    }

    /**
     * Lower-case and reduce punctuation to single spaces, so "CR-V", "cr v"
     * and "crv" can all be compared against the same stored spellings.
     */
    private function normalise(string $message): string
    {
        $text = mb_strtolower(trim($message));
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private function extractYear(string $text): ?int
    {
        if (!preg_match_all('/\b(19|20)\d{2}\b/', $text, $matches)) {
            return null;
        }

        $years = array_map('intval', $matches[0]);
        $plausible = array_filter($years, fn (int $y) => $y >= self::EARLIEST_YEAR && $y <= (int) date('Y') + 1);

        return $plausible ? max($plausible) : null;
    }

    /**
     * The longest stored name contained in the message wins, so "montero
     * sport" is preferred over a bare "montero" when both would match.
     */
    private function longestModelMatch(string $text): ?string
    {
        $padded = ' ' . $text . ' ';
        $best = null;

        foreach ($this->all() as $spec) {
            foreach ($spec->searchNames() as $name) {
                $needle = ' ' . $this->normalise($name) . ' ';

                if (!str_contains($padded, $needle)) {
                    continue;
                }

                if ($best === null || mb_strlen($name) > mb_strlen($best)) {
                    $best = $name;
                }
            }
        }

        return $best;
    }
}
