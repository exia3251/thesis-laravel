<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /** Rows per page when a request does not ask for a particular size. */
    protected const DEFAULT_PER_PAGE = 20;

    /**
     * One response shape for every paginated list, so the front end has a
     * single pagination control rather than one per screen.
     *
     * @param  callable|null  $mapper  applied to each row before it is sent
     */
    protected function paginated(LengthAwarePaginator $page, ?callable $mapper = null, array $extra = []): JsonResponse
    {
        $rows = collect($page->items());

        return response()->json($extra + [
            'success' => true,
            'data' => $mapper ? $rows->map($mapper)->values()->all() : $rows->values()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
        ]);
    }

    /** Clamped so a caller cannot ask for the whole table in one request. */
    protected function perPage(int $default = self::DEFAULT_PER_PAGE): int
    {
        return (int) min(max((int) request()->integer('per_page', $default), 5), 100);
    }
}
