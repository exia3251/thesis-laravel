<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * No action may be sent twice because the button said nothing the first time.
 *
 * Every action on these screens goes to the server and comes back, and in
 * between the button looked exactly as it had a moment earlier -- so people
 * pressed it again. Two sales, each taking its own stock. Two orders for one
 * cart. Two stock movements to find and unpick from the transaction history.
 *
 * Each of those was fixed where it was found, which is how the next one gets
 * written unguarded. This reads the views instead and asks the question of all
 * of them at once: does every handler that changes something on the server
 * also hold its button while it waits?
 *
 * It is a source sweep rather than a behaviour test because the behaviour is
 * in the browser, and because a new handler added next term is exactly the
 * case a behaviour test would not cover.
 */
class AsyncButtonSafetyTest extends TestCase
{
    /** A request that changes something, rather than one that reads. */
    private const MUTATION = "/method:\s*'(POST|PUT|PATCH|DELETE)'/";

    /** Where one handler ends and the next begins. */
    private const BOUNDARY = "/(?:async\s+function\s+\w+|addEventListener\('submit'|\.onsubmit\s*=)/";

    /**
     * Anything that stops a second press landing: the shared helpers, a hand
     * written disable, or the chat widget's own in-flight flag.
     */
    private const GUARD = "/withBusy\(|startBusy\(|\.disabled\s*=\s*true|chatBusy/";

    #[Test]
    public function every_handler_that_changes_something_holds_its_button(): void
    {
        $unguarded = [];

        foreach ($this->views() as $path => $source) {
            if (! preg_match(self::MUTATION, $source)) {
                continue;
            }

            foreach ($this->handlers($source) as $handler) {
                if (preg_match(self::MUTATION, $handler) && ! preg_match(self::GUARD, $handler)) {
                    $unguarded[] = $path . ' -- ' . $this->handlerName($handler);
                }
            }
        }

        $this->assertSame([], $unguarded, "These send a request with nothing holding the button:\n"
            . implode("\n", $unguarded));
    }

    /**
     * The shared helpers themselves, which everything above leans on. Named
     * separately because the sweep would pass just as happily if they were
     * deleted and nothing called them.
     */
    #[Test]
    public function the_shared_helpers_disable_the_button_and_put_it_back(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        foreach (['function withBusy', 'function startBusy', 'function busyButtonOf'] as $helper) {
            $this->assertStringContainsString($helper, $layout, "{$helper} is gone, and the pages calling it will throw.");
        }

        // Disabling is what stops the second press; the spinner only explains
        // the wait. Both halves have to be there.
        $this->assertSame(2, preg_match_all('/button\.disabled = true;/', $layout));
        $this->assertSame(2, preg_match_all('/animate-spin/', $layout));

        // Restored in a finally, so a failed request leaves a usable button
        // rather than a dead one.
        $this->assertStringContainsString('} finally {', $layout);
    }

    #[Test]
    public function the_helpers_live_where_every_page_can_reach_them(): void
    {
        /*
         * These pages load the stylesheet and no script bundle, so a helper in
         * resources/js would not be on the page at all. The base layout is the
         * one file every screen passes through.
         */
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString("@vite(['resources/css/app.css'])", $layout);
        $this->assertStringNotContainsString('resources/js/app.js', $layout);

        foreach (['admin', 'customer', 'bare'] as $child) {
            $this->assertStringContainsString(
                "@extends('layouts.app')",
                file_get_contents(resource_path("views/layouts/{$child}.blade.php")),
                "layouts/{$child} no longer inherits the shared helpers."
            );
        }
    }

    /**
     * Every Blade view, with block comments taken out.
     *
     * Commented-out code is not behaviour, and one disabled handler is parked
     * in the products page waiting on a decision about catalogue imports.
     */
    private function views(): array
    {
        $views = [];

        foreach ($this->files(resource_path('views')) as $path) {
            $source = preg_replace('#/\*.*?\*/#s', '', file_get_contents($path));

            $views[str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $path)] = $source;
        }

        $this->assertNotEmpty($views, 'No views were read, so this test proved nothing.');

        return $views;
    }

    private function files(string $directory): array
    {
        $found = [];

        foreach (scandir($directory) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                $found = array_merge($found, $this->files($path));
            } elseif (str_ends_with($entry, '.blade.php')) {
                $found[] = $path;
            }
        }

        return $found;
    }

    /** Splits a view's script into one chunk per handler. */
    private function handlers(string $source): array
    {
        preg_match_all(self::BOUNDARY, $source, $matches, PREG_OFFSET_CAPTURE);

        $starts = array_map(fn ($match) => $match[1], $matches[0]);

        if ($starts === []) {
            return [$source];
        }

        $chunks = [];
        $starts[] = strlen($source);

        for ($i = 0; $i < count($starts) - 1; $i++) {
            $chunks[] = substr($source, $starts[$i], $starts[$i + 1] - $starts[$i]);
        }

        return $chunks;
    }

    /** Enough of a handler to find it by. */
    private function handlerName(string $handler): string
    {
        preg_match('/^(?:async\s+function\s+(\w+)|addEventListener|\.onsubmit)/', trim($handler), $matches);

        return $matches[1] ?? trim(substr($handler, 0, 60));
    }
}
