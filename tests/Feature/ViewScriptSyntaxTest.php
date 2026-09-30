<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A script block that does not parse takes everything after it with it.
 *
 * The admin layout carried a stray `});` on the first line of its script, left
 * behind when a block of JavaScript was moved out to the base layout. The
 * browser refused the whole block, so `toggleAdminNav` was never defined and
 * the back office's menu button did nothing on a phone -- silently, on every
 * admin page, for as long as it took somebody to open a console.
 *
 * That is the shape of the fault worth guarding: not a subtle expression, but
 * a block that opens with a closing delimiter because an edit cut in the wrong
 * place. Nothing here parses JavaScript -- these views are Blade, so their
 * source is not valid JavaScript until it is rendered. It checks the one thing
 * that made the real bug, and says so rather than pretending to more.
 */
class ViewScriptSyntaxTest extends TestCase
{
    #[Test]
    public function no_script_block_begins_with_a_closing_delimiter(): void
    {
        $broken = [];

        foreach ($this->scriptBlocks() as $path => $blocks) {
            foreach ($blocks as $index => $block) {
                $first = $this->firstMeaningfulLine($block);

                if ($first !== '' && preg_match('/^[}\])]/', $first)) {
                    $broken[] = "{$path} (block {$index}) starts with: {$first}";
                }
            }
        }

        $this->assertSame([], $broken, "A script block opens with a closing delimiter, so the browser "
            . "will refuse all of it:\n" . implode("\n", $broken));
    }

    #[Test]
    public function every_script_block_is_closed(): void
    {
        $unclosed = [];

        foreach ($this->views() as $path => $source) {
            $opened = preg_match_all('/<script\b/i', $source);
            $closed = preg_match_all('#</script>#i', $source);

            if ($opened !== $closed) {
                $unclosed[] = "{$path}: {$opened} opened, {$closed} closed";
            }
        }

        $this->assertSame([], $unclosed, "Script tags do not pair up:\n" . implode("\n", $unclosed));
    }

    #[Test]
    public function every_pushed_stack_is_closed(): void
    {
        // The other half of the same accident: a cut that eats an @endpush
        // swallows the rest of the file into the stack, and the page renders
        // with pieces missing rather than failing outright.
        $unbalanced = [];

        foreach ($this->views() as $path => $source) {
            foreach ([['@push', '@endpush'], ['@section', '@endsection'], ['@php', '@endphp']] as [$open, $close]) {
                $opened = preg_match_all('/' . preg_quote($open, '/') . '\b/', $source);
                $closed = preg_match_all('/' . preg_quote($close, '/') . '\b/', $source);

                // @section('name', 'value') closes itself, so only the block
                // form is counted.
                if ($open === '@section') {
                    $opened -= preg_match_all("/@section\([^)]*,[^)]*\)/", $source);
                }

                if ($opened !== $closed) {
                    $unbalanced[] = "{$path}: {$opened} {$open} against {$closed} {$close}";
                }
            }
        }

        $this->assertSame([], $unbalanced, "Blade directives do not pair up:\n" . implode("\n", $unbalanced));
    }

    /** @return array<string, string> */
    private function views(): array
    {
        $views = [];

        foreach ($this->files(resource_path('views')) as $path) {
            $views[str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $path)] = file_get_contents($path);
        }

        $this->assertNotEmpty($views, 'No views were read, so this test proved nothing.');

        return $views;
    }

    /** @return array<string, array<int, string>> */
    private function scriptBlocks(): array
    {
        $blocks = [];

        foreach ($this->views() as $path => $source) {
            if (preg_match_all('#<script\b[^>]*>(.*?)</script>#is', $source, $matches)) {
                $blocks[$path] = $matches[1];
            }
        }

        return $blocks;
    }

    /** The first line that is neither blank nor a comment. */
    private function firstMeaningfulLine(string $block): string
    {
        foreach (preg_split('/\R/', $block) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '//') || str_starts_with($line, '/*')
                || str_starts_with($line, '*') || str_starts_with($line, '{{--')) {
                continue;
            }

            return $line;
        }

        return '';
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
}
