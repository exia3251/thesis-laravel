<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Support\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What happens when somebody types SQL into a search box, or markup into a
 * field the whole shop then reads.
 *
 * Two of these were found live and are named below with the page they were
 * on. Both were the same shape: a value a person typed, put into something
 * that interprets it -- one into SQL, one into markup -- without being told it
 * was data.
 *
 * The SQL half held up. Eloquent binds its parameters, and the one raw
 * fragment in the system puts the search term in as a binding and guards the
 * column name it interpolates. These tests exist to keep that true rather than
 * to report a fix.
 *
 * The markup half did not. These pages build their rows by concatenating
 * strings into innerHTML, which means every value that came from a person has
 * to be escaped by hand on the way in -- and four of them were not.
 */
class InjectionSafetyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'email' => 'security@raney.test',
            'password' => 'a-password',
            'full_name' => 'Security Reviewer',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->product('Solar Premium 5W-30', 'SOLAR');
        $this->product('Canroyal Diesel 15W-40', 'CANROYAL');
    }

    private function product(string $name, string $brand): Product
    {
        $product = Product::create([
            'product_name' => $name,
            'brand' => $brand,
            'oil_type' => 'Synthetic',
            'viscosity_grade' => '5W-30',
            'unit' => '1 Liter',
            'price' => 500,
        ]);

        Inventory::create([
            'product_id' => $product->product_id,
            'quantity' => 10,
            'reorder_level' => 2,
        ]);

        return $product;
    }

    /** The payloads worth trying against a LIKE search. */
    public static function sqlPayloads(): array
    {
        return [
            'or true'            => ["' OR '1'='1"],
            'comment out'        => ["' OR 1=1 -- "],
            'union select'       => ["' UNION SELECT NULL,NULL,NULL,NULL,NULL -- "],
            'stacked drop'       => ["'; DROP TABLE products; -- "],
            'stacked delete'     => ["'; DELETE FROM users; -- "],
            'quote alone'        => ["'"],
            'backslash'          => ['\\'],
            'sleep'              => ["' OR SLEEP(5) -- "],
            'into outfile'       => ["' INTO OUTFILE '/tmp/x' -- "],
            'percent wildcards'  => ['%'],
            'underscore wildcard'=> ['_'],
        ];
    }

    // ---- SQL -----------------------------------------------------------

    #[Test]
    #[DataProvider('sqlPayloads')]
    public function a_search_payload_is_treated_as_text_not_as_sql(string $payload): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->getJson('/admin-api/products?search=' . urlencode($payload))
            ->assertOk();

        // Read as text, none of these names a product, so the honest answer is
        // an empty list -- never the whole table, which is what a payload that
        // had been interpreted would have returned.
        $this->assertSame([], $response->json('data'), "\"{$payload}\" was not treated as text.");

        // And nothing was executed: the tables it tried to drop are still here.
        $this->assertSame(2, Product::count());
        $this->assertSame(1, User::count());
    }

    #[Test]
    #[DataProvider('sqlPayloads')]
    public function the_same_payloads_do_nothing_to_the_shop_search(string $payload): void
    {
        // The catalogue is searchable without signing in, which makes it the
        // one of these an outsider can actually reach.
        $this->getJson('/shop-api/products?search=' . urlencode($payload))->assertOk();

        $this->assertSame(2, Product::count());
    }

    #[Test]
    #[DataProvider('sqlPayloads')]
    public function the_user_and_sales_searches_hold_too(string $payload): void
    {
        foreach (['/admin-api/users', '/admin-api/sales'] as $endpoint) {
            $this->actingAs($this->admin, 'staff')
                ->getJson($endpoint . '?search=' . urlencode($payload))
                ->assertOk();
        }

        $this->assertSame(1, User::count());
    }

    #[Test]
    public function a_real_search_still_finds_what_it_should(): void
    {
        // The tests above pass trivially if search is broken, so this one says
        // it is not: the escaping did not come at the cost of the feature.
        $found = $this->actingAs($this->admin, 'staff')
            ->getJson('/admin-api/products?search=' . urlencode('5W-30'))
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($found);
    }

    #[Test]
    public function the_search_term_travels_as_a_binding_rather_than_as_sql(): void
    {
        /*
         * The one raw fragment in the system is in Search, which lowercases a
         * column and strips punctuation out of it. The term goes in as a
         * binding; only the column name is interpolated.
         */
        $sql = null;

        DB::listen(function ($query) use (&$sql) {
            if (str_contains($query->sql, 'REPLACE')) {
                $sql = $query;
            }
        });

        Product::query()->tap(fn ($q) => Search::apply($q, "5w-30' OR 1=1 -- ", ['product_name', 'brand']))->get();

        $this->assertNotNull($sql, 'The punctuation-stripping branch never ran, so this proved nothing.');
        $this->assertStringNotContainsString('OR 1=1', $sql->sql, 'The term was written into the SQL itself.');
        $this->assertStringContainsString('?', $sql->sql);
    }

    #[Test]
    public function a_column_name_that_is_not_a_plain_identifier_is_refused(): void
    {
        // Callers pass literals, never input -- but the fragment interpolates
        // them, so the guard is what makes that a convention rather than a
        // requirement nobody wrote down.
        $guard = new \ReflectionMethod(Search::class, 'isPlainColumnName');
        $guard->setAccessible(true);

        foreach (['product_name', 'products.brand', 'full_name'] as $allowed) {
            $this->assertTrue($guard->invoke(null, $allowed), "{$allowed} should be allowed.");
        }

        foreach (["name); DROP TABLE users; --", "name' OR '1'='1", 'name)', '1=1', '', 'a b'] as $refused) {
            $this->assertFalse($guard->invoke(null, $refused), "{$refused} should be refused.");
        }
    }

    #[Test]
    public function no_raw_sql_fragment_is_built_from_a_request(): void
    {
        // The rule the codebase actually follows, written down: raw fragments
        // are constants, and anything from outside arrives as a binding.
        $offenders = [];

        foreach ($this->phpFiles(app_path()) as $path) {
            $source = file_get_contents($path);

            if (preg_match('/(whereRaw|selectRaw|orderByRaw|havingRaw|DB::raw)\s*\([^)]*\$(request|input|_GET|_POST)/i', $source, $m)) {
                $offenders[] = basename($path) . ': ' . $m[0];
            }
        }

        $this->assertSame([], $offenders, "Request data is being written into raw SQL:\n" . implode("\n", $offenders));
    }

    // ---- Markup --------------------------------------------------------

    #[Test]
    public function a_brand_containing_markup_is_stored_exactly_as_typed(): void
    {
        /*
         * Storing it verbatim is correct -- the database holds what somebody
         * typed, and stripping tags on the way in would corrupt a legitimate
         * name like "Shell Helix <HX7>". The escaping belongs at the point it
         * is drawn, which is what the sweep below is about.
         */
        $payload = 'Solar"><img src=x onerror="alert(1)">';

        $this->actingAs($this->admin, 'staff')
            ->postJson('/admin-api/products', [
                'product_name' => 'Probe Oil',
                'brand' => $payload,
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W-30',
                'unit' => '1 Liter',
                'price' => 1,
                'quantity' => 1,
                'reorder_level' => 1,
            ])
            ->assertOk();

        $this->assertSame($payload, Product::where('product_name', 'Probe Oil')->first()->brand);
    }

    #[Test]
    public function no_page_echoes_a_value_without_escaping_it(): void
    {
        $this->assertSame([], $this->unescaped(), "These put somebody's typed text into markup unescaped:\n"
            . implode("\n", $this->unescaped()));
    }

    #[Test]
    public function the_four_that_were_found_live_stay_escaped(): void
    {
        /*
         * Named individually, because a sweep can be loosened by accident and
         * these are the ones that were actually reachable. The first was the
         * worst placed: a brand is typed by staff and drawn into the shop's
         * filter for every visitor, so markup in one ran in the browser of
         * everybody who opened the catalogue.
         */
        $fixed = [
            'customer/shop.blade.php' => 'escapeHtml(brand)',
            'admin/analytics.blade.php' => 'escapeHtml(b.brand)',
            'admin/users.blade.php' => 'escapeHtml(m)',
            'admin/products.blade.php' => 'escapeHtml(messages[0])',
        ];

        foreach ($fixed as $view => $call) {
            $this->assertStringContainsString(
                $call,
                file_get_contents(resource_path('views/' . $view)),
                "{$view} no longer escapes the value that was exploitable there."
            );
        }
    }

    #[Test]
    public function blade_never_prints_anything_unescaped(): void
    {
        $offenders = [];

        foreach ($this->phpFiles(resource_path('views')) as $path) {
            if (str_contains(file_get_contents($path), '{!!')) {
                $offenders[] = basename($path);
            }
        }

        $this->assertSame([], $offenders, 'These use Blade\'s unescaped echo: ' . implode(', ', $offenders));
    }

    #[Test]
    public function the_escaper_covers_quotes_as_well_as_brackets(): void
    {
        // Several of these values land inside an attribute, where a quote is
        // all it takes -- which is exactly how the brand filter was reached.
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        foreach (['&amp;', '&lt;', '&gt;', '&quot;', '&#39;'] as $entity) {
            $this->assertStringContainsString($entity, $layout, "escapeHtml does not produce {$entity}.");
        }
    }

    #[Test]
    public function uploads_cannot_carry_a_script(): void
    {
        /*
         * An SVG is a document, not a picture: one served from our own origin
         * would run whatever script it contained. Every upload here is limited
         * to raster formats, and to `image`, which checks the file rather than
         * its name.
         */
        $rules = [];

        foreach ($this->phpFiles(app_path('Http/Controllers')) as $path) {
            if (preg_match_all('/mimes:([a-z,]+)/', file_get_contents($path), $m)) {
                $rules = array_merge($rules, $m[1]);
            }
        }

        $this->assertNotEmpty($rules, 'No upload rules were found, so this proved nothing.');

        foreach ($rules as $rule) {
            foreach (['svg', 'html', 'htm', 'xml', 'php', 'js'] as $dangerous) {
                $this->assertNotContains($dangerous, explode(',', $rule), "An upload accepts .{$dangerous} files.");
            }
        }
    }

    // ---- The sweep -----------------------------------------------------

    /**
     * Values a person typed, dropped into markup without being escaped.
     *
     * Only fields whose contents came from somebody: an id, a loop counter or
     * a class name this code computed itself cannot carry markup that matters.
     *
     * @return array<int, string>
     */
    private function unescaped(): array
    {
        $personData = [
            'product_name', 'brand', 'oil_type', 'viscosity_grade',
            'customer_name', 'full_name', 'email', 'phone',
            'house_street', 'barangay', 'province', 'full_address',
            'receipt_no', 'delivery_no', 'reference_no',
            'reason', 'cancellation_reason', 'refund_reason', 'admin_notes',
            'question', 'answer', 'keywords', 'aliases',
        ];

        $field = '/\b(' . implode('|', $personData) . ')\b/';
        $safe = '/escapeHtml\(|formatCurrency\(|\.toFixed\(|\.length\b/';

        $found = [];

        foreach ($this->phpFiles(resource_path('views')) as $path) {
            foreach ($this->markupLiterals(file_get_contents($path)) as $literal) {
                preg_match_all('/\$\{([^{}]*(?:\{[^{}]*\}[^{}]*)*)\}/', $literal, $interpolations);

                foreach ($interpolations[1] as $expression) {
                    if (preg_match($safe, $expression) || ! preg_match($field, $expression)) {
                        continue;
                    }

                    $found[] = basename($path) . ': ${' . trim($expression) . '}';
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Template literals that build markup.
     *
     * Scanned per literal rather than by looking at a window after each
     * `innerHTML =`, which swept up whatever happened to sit nearby -- it
     * flagged a name assigned to `textContent` a few lines below, where
     * escaping would be the bug rather than the fix, because the browser
     * would then show the reader `&amp;`.
     *
     * A literal counts as markup when it opens a tag. That is the only place
     * escaping is owed: a value going into textContent is already inert.
     *
     * @return array<int, string>
     */
    private function markupLiterals(string $source): array
    {
        preg_match_all('/`(?:[^`\\\\]|\\\\.)*`/s', $source, $matches);

        return array_values(array_filter(
            $matches[0],
            fn (string $literal) => (bool) preg_match('/<\/?[a-zA-Z][a-zA-Z0-9-]*[\s>\/]/', $literal)
        ));
    }

    /** @return array<int, string> */
    private function phpFiles(string $directory): array
    {
        $found = [];

        foreach (scandir($directory) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                $found = array_merge($found, $this->phpFiles($path));
            } elseif (str_ends_with($entry, '.php')) {
                $found[] = $path;
            }
        }

        return $found;
    }
}
