<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Issues the next number in a document series, formatted OR-2026-000001.
 *
 * Numbering restarts each year, which is how paper books are kept, and makes
 * the year legible in the reference a customer quotes back over the phone.
 */
class DocumentNumber
{
    public const RECEIPT  = 'OR';
    public const DELIVERY = 'DR';

    public static function next(string $series, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        // Take the counter row for this series and year, creating it on first
        // use, then hold it just long enough to claim a number. Anything else
        // issuing at the same moment waits rather than duplicating.
        $number = DB::transaction(function () use ($series, $year) {
            $row = DB::table('document_sequences')
                ->where('series', $series)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                DB::table('document_sequences')->insert([
                    'series' => $series,
                    'year' => $year,
                    'last_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return 1;
            }

            $next = $row->last_number + 1;

            DB::table('document_sequences')
                ->where('id', $row->id)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return $next;
        });

        return sprintf('%s-%d-%06d', $series, $year, $number);
    }
}
