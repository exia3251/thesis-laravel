<?php

namespace App\Rules;

use App\Models\PsgcLocation;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

/**
 * The place has to be real, and inside the one named above it.
 *
 * The three address boxes narrow each other in the browser, but a browser is
 * not where this has to hold. Anything can post to these endpoints, and the
 * fields were free text before, so this is what actually keeps a barangay from
 * being saved under a city it does not sit in.
 *
 * Matching goes by code when the form sent one, because that is exact. It
 * falls back to the name so that an address saved before these lists existed
 * still validates when its owner edits something else on the same form -- and
 * so that a submission made without JavaScript is judged on what it says
 * rather than on what it failed to carry.
 */
class RealPlace implements ValidationRule, DataAwareRule
{
    private array $data = [];

    /**
     * @param string      $level       province, city or barangay
     * @param string|null $parentField the field naming the place this sits in
     */
    public function __construct(
        private string $level,
        private ?string $parentField = null,
    ) {
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return; // 'required' has its own say; this rule only judges what is there.
        }

        /*
         * An install whose place list was never seeded lets the address
         * through.
         *
         * Judged against an empty table every address is wrong, so failing
         * closed would mean nobody could register at all -- on a fresh clone
         * where someone ran `migrate` without `--seed`, the first thing they
         * would meet is a sign-up form that refuses every address in the
         * country and blames them for it.
         *
         * So a missing list degrades to the free text these fields were
         * before, and says so where whoever installed it will see it. It is
         * deliberately loud: this is an incomplete install, not a mode anyone
         * should settle into.
         */
        if (! $this->listExists()) {
            Log::error('psgc_locations is empty, so addresses are not being checked against it. '
                . 'Run: php artisan db:seed --class=PsgcLocationSeeder');

            return;
        }

        $parentCode = $this->parentCode();

        // The parent is missing or itself unreal. Its own rule is already
        // failing, and a second complaint about this box would only point the
        // reader at the wrong one.
        if ($this->parentField !== null && $parentCode === null) {
            return;
        }

        if ($this->resolve($attribute, $value, $parentCode) === null) {
            $fail($this->message($value));
        }
    }

    /**
     * Whether there is a list to check against at all.
     *
     * Asked afresh every time. It was held in a static at first, to save two
     * of the three queries an address costs -- but a static outlives the
     * request that filled it, so the answer went on being "yes, there is a
     * list" after the list had gone, and the degradation this guards was
     * never reached. An index-only existence check on a table that is never
     * written is not worth being wrong about.
     */
    private function listExists(): bool
    {
        return PsgcLocation::query()->exists();
    }

    /** The place this one has to sit inside, as a code. */
    private function parentCode(): ?string
    {
        if ($this->parentField === null) {
            return null;
        }

        $parentLevel = $this->parentField === 'province' ? 'province' : 'city';
        $grandparent = $this->parentField === 'province'
            ? null
            : $this->codeFor('province', 'province', null);

        return $this->codeFor($this->parentField, $parentLevel, $grandparent);
    }

    private function codeFor(string $field, string $level, ?string $parentCode): ?string
    {
        $name = $this->data[$field] ?? null;

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        $posted = $this->data[$field . '_code'] ?? null;

        if (is_string($posted) && preg_match('/^\d{9}$/', $posted)) {
            $row = PsgcLocation::where('code', $posted)->where('level', $level)->first();

            /*
             * The code has to agree with the name beside it.
             *
             * It was trusted on its own at first, which meant a request could
             * post one place's name with another place's code and have the
             * name saved unchecked -- the exact thing this rule exists to
             * stop, reachable by anyone willing to edit a form field. The code
             * is a shortcut for looking the name up, never a substitute for
             * it, so a pair that disagree falls through to the name below.
             */
            $agrees = $row
                && ($parentCode === null || $row->parent_code === $parentCode)
                && PsgcLocation::normalise($row->name) === PsgcLocation::normalise($name);

            if ($agrees) {
                return $row->code;
            }
        }

        return PsgcLocation::findNamed($name, $level, $parentCode)?->code;
    }

    private function resolve(string $attribute, string $value, ?string $parentCode): ?string
    {
        return $this->codeFor($attribute, $this->level, $parentCode);
    }

    private function message(string $value): string
    {
        return match ($this->level) {
            'province' => "We could not find the province \"{$value}\". Choose one from the list.",
            'city' => "\"{$value}\" is not a city or municipality in the province you chose. Choose one from the list.",
            default => "\"{$value}\" is not a barangay in the city you chose. Choose one from the list.",
        };
    }
}
