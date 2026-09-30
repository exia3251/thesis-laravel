<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every password box can show what was typed into it.
 *
 * Two of the thirteen had the eye: the sign-in pages, where it was written out
 * by hand, twice. The other eleven did not -- including both halves of "create
 * a password / confirm your password", which is the one place a typo cannot be
 * seen and costs the whole form. Somebody mistypes one of the two, the form
 * refuses the account, and the mismatch they are being told about is still
 * invisible.
 *
 * Written as a sweep rather than page by page, because the fault was never
 * that one page was wrong -- it was that nothing said every page had to be
 * right, so each new password box started out without one.
 */
class PasswordVisibilityTest extends TestCase
{
    #[Test]
    public function every_password_box_has_an_eye_beside_it(): void
    {
        $missing = [];

        foreach ($this->views() as $path => $source) {
            foreach ($this->passwordFields($source) as $field) {
                if (! $this->hasEye($source, $field['at'])) {
                    $missing[] = "{$path}: {$field['name']}";
                }
            }
        }

        $this->assertSame([], $missing, "These password boxes cannot be read back:\n" . implode("\n", $missing));
    }

    #[Test]
    public function the_eye_is_one_partial_rather_than_a_copy_per_page(): void
    {
        // It was two copies before, and the two had already drifted apart in
        // their wording. Thirteen copies would have been thirteen.
        $partial = resource_path('views/partials/password-eye.blade.php');

        $this->assertFileExists($partial);

        $copies = 0;

        foreach ($this->views() as $path => $source) {
            if ($path !== 'partials' . DIRECTORY_SEPARATOR . 'password-eye.blade.php'
                && str_contains($source, 'class="eye-open')) {
                $copies++;
            }
        }

        $this->assertSame(0, $copies, 'The eye icon is written out somewhere instead of being included.');
    }

    #[Test]
    public function the_toggle_lives_where_every_page_can_reach_it(): void
    {
        /*
         * These pages load the stylesheet and no script bundle, so a helper in
         * resources/js would not be on the page at all. The base layout is the
         * one file every screen passes through -- and it was defined on the
         * two sign-in pages only, which is why no other page could have used
         * the button even if it had one.
         */
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('function togglePassword', $layout);

        $elsewhere = 0;

        foreach ($this->views() as $path => $source) {
            if ($path !== 'layouts' . DIRECTORY_SEPARATOR . 'app.blade.php'
                && str_contains($source, 'function togglePassword')) {
                $elsewhere++;
            }
        }

        $this->assertSame(0, $elsewhere, 'togglePassword is defined outside the base layout.');
    }

    #[Test]
    public function showing_a_password_never_submits_the_form(): void
    {
        // A button inside a form submits it unless told otherwise, so the one
        // that reveals a password would have sent a half-filled registration.
        $partial = file_get_contents(resource_path('views/partials/password-eye.blade.php'));

        $this->assertStringContainsString('type="button"', $partial);
    }

    #[Test]
    public function the_eye_is_skipped_when_tabbing_through_a_form(): void
    {
        // Tab should go from the password to the button that submits it, not
        // into an icon. It stays reachable by pointer, which is how it is used.
        $partial = file_get_contents(resource_path('views/partials/password-eye.blade.php'));

        $this->assertStringContainsString('tabindex="-1"', $partial);
        $this->assertStringContainsString('aria-label="Show password"', $partial);
    }

    #[Test]
    public function the_pages_that_ask_for_a_password_actually_render_one(): void
    {
        // The sweep above proves the markup pairs up. This proves the pages it
        // sweeps are pages that exist and still render.
        foreach (['/shop/login', '/admin/login', '/shop/register'] as $url) {
            $page = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('togglePassword(this)', $page, "{$url} has no way to show a password.");
        }
    }

    /**
     * Each password input in a view, with where it sits.
     *
     * @return array<int, array{name: string, at: int}>
     */
    private function passwordFields(string $source): array
    {
        preg_match_all('/<input[^>]*type="password"[^>]*>/', $source, $matches, PREG_OFFSET_CAPTURE);

        return array_map(function ($match) {
            preg_match('/id="([^"]+)"/', $match[0], $id);

            return ['name' => $id[1] ?? 'unnamed', 'at' => $match[1] + strlen($match[0])];
        }, $matches[0]);
    }

    /**
     * Whether an eye follows this input closely enough to be its own.
     *
     * Looked for in the markup straight after the field rather than anywhere
     * in the file, so a page with three password boxes and one eye fails --
     * which is what the profile and account forms were.
     */
    private function hasEye(string $source, int $after): bool
    {
        $window = substr($source, $after, 400);

        return str_contains($window, "@include('partials.password-eye'")
            || str_contains($window, 'togglePassword(this)');
    }

    /** @return array<string, string> */
    private function views(): array
    {
        $views = [];

        foreach ($this->files(resource_path('views')) as $path) {
            /*
             * Blade comments come out first. The sweep measures how far the
             * eye sits from its field, and several of these fields carry a
             * paragraph explaining why they need one -- which pushed the
             * include out of range and failed the very pages that had it.
             * A comment is not markup, so it should not count as distance.
             */
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($path));

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
}
