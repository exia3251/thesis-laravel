<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder;

/**
 * How a typed search term is matched against rows.
 *
 * Every search box in this system used the same shape: take what was typed,
 * wrap it in per cent signs, and ask the database for rows containing that
 * string. It reads like it works, and it fails on the two things people
 * actually type.
 *
 * "5W-30" found nothing, because the column holds 5W30. So did "5W 30". The
 * grade is written with the hyphen everywhere a customer has ever seen it --
 * on the bottle, in the handbook, in this system's own chat replies -- and
 * typing it into the search returned an empty shelf.
 *
 * "motor oil" found nothing either, because no product is called that. They
 * are called "Solar Premium Series Motor Engine Oil", and a single substring
 * cannot span the word in between.
 *
 * So: split what was typed into words, require every word to appear somewhere
 * among the columns, and let a word match with its punctuation removed as well
 * as with it. "5W-30" finds 5W30, "motor oil" finds the motor engine oils, and
 * a longer phrase narrows rather than suddenly matching nothing.
 */
final class Search
{
    /**
     * Narrow a query to rows matching every word in the term.
     *
     * @param  array<int,string>  $columns
     */
    public static function apply(Builder $query, ?string $term, array $columns): Builder
    {
        $words = self::words($term);

        if ($words === [] || $columns === []) {
            return $query;
        }

        return $query->where(function (Builder $all) use ($words, $columns) {
            foreach ($words as $word) {
                // Each word has to land somewhere, but not all in one column:
                // "solar 5w30" is a brand in one and a grade in another.
                $all->where(function (Builder $any) use ($word, $columns) {
                    $plain = self::plain($word);

                    foreach ($columns as $column) {
                        $any->orWhere($column, 'like', '%' . $word . '%');

                        /*
                         * And again with the punctuation taken out of both
                         * sides, so it does not matter which of them carries
                         * the hyphen: "5W-30" finds a column holding 5W30,
                         * and "5w30" finds one holding 5W-30.
                         */
                        if ($plain !== '' && self::isPlainColumnName($column)) {
                            $any->orWhereRaw(
                                "REPLACE(REPLACE(REPLACE(LOWER({$column}), '-', ''), ' ', ''), '/', '') LIKE ?",
                                ['%' . $plain . '%']
                            );
                        }
                    }
                });
            }
        });
    }

    /**
     * The words in a term, lowercased, with the empty ones dropped.
     *
     * @return array<int,string>
     */
    public static function words(?string $term): array
    {
        $term = trim((string) $term);

        if ($term === '') {
            return [];
        }

        return array_values(array_filter(
            preg_split('/\s+/', mb_strtolower($term)) ?: [],
            static fn (string $word) => $word !== ''
        ));
    }

    /** A word with its punctuation taken out: "5w-30" becomes "5w30". */
    private static function plain(string $word): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', $word) ?? '';
    }

    /**
     * A word as typed, and again without its punctuation.
     *
     * Only the second form when it differs, so an ordinary word is looked for
     * once rather than twice.
     *
     * @return array<int,string>
     */
    private static function formsOf(string $word): array
    {
        $plain = self::plain($word);

        return $plain !== '' && $plain !== $word ? [$word, $plain] : [$word];
    }

    /**
     * Whether a column name is safe to put into raw SQL.
     *
     * Callers pass these as literals, never user input, but the raw fragment
     * above interpolates them and a guard costs nothing.
     */
    private static function isPlainColumnName(string $column): bool
    {
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $column);
    }

    /**
     * The same rule, for lists already in memory.
     *
     * @param  array<int,?string>  $fields
     */
    public static function matches(?string $term, array $fields): bool
    {
        $words = self::words($term);

        if ($words === []) {
            return true;
        }

        $haystack = mb_strtolower(implode(' ', array_filter($fields, 'is_string')));
        $plainHaystack = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $haystack) ?? $haystack;

        foreach ($words as $word) {
            $found = false;

            foreach (self::formsOf($word) as $form) {
                if (str_contains($haystack, $form) || str_contains($plainHaystack, $form)) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                return false;
            }
        }

        return true;
    }
}
