<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewMedia;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Support\ReviewMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\MakesImages;
use Tests\TestCase;

/**
 * What a customer may attach to a review.
 *
 * Everything here arrives from a stranger and is then served back to other
 * customers, so the two questions are what is accepted and how large it may
 * be. Both are settings rather than numbers buried in a controller, and
 * both are checked against the file's own contents: an extension is a name
 * the uploader chose.
 *
 * The ceilings differ by kind on purpose. One number for both would have to
 * be the video's, which would let somebody upload a twenty-megabyte
 * photograph.
 */
class ReviewMediaTest extends TestCase
{
    use RefreshDatabase;
    use MakesImages;

    private function customer(string $email = 'buyer@example.com'): User
    {
        return User::create([
            'email' => $email,
            'password' => 'secret12345',
            'full_name' => 'Boy Santos',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }

    private function product(): Product
    {
        $product = Product::create([
            'product_name' => 'Test Engine Oil 5W30',
            'brand' => 'SOLAR',
            'oil_type' => 'Synthetic',
            'viscosity_grade' => '5W30',
            'unit' => '1 Liter',
            'price' => 450,
            'reorder_level' => 5,
        ]);
        Inventory::create(['product_id' => $product->product_id, 'quantity' => 20]);

        return $product;
    }

    /** A delivered order, which is what entitles a review at all. */
    private function delivered(User $user, Product $product): Sale
    {
        $sale = Sale::create([
            'user_id' => $user->user_id,
            'customer_name' => $user->full_name,
            'total_amount' => 450,
            'payment_method' => 'cash_on_delivery',
            'payment_plan' => Sale::PLAN_COD,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'order_status' => Sale::STATUS_ACTIVE,
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 450,
            'subtotal' => 450,
        ]);

        return $sale;
    }

    /**
     * A real file on disk, named as it will be uploaded.
     *
     * Written with its extension rather than through tempnam(), because
     * finfo cannot read an extensionless temporary file on Windows and the
     * request then fails with a 500 instead of the refusal being tested.
     */
    private function fileOf(string $name, int $kilobytes, string $mime, ?string $contents = null): UploadedFile
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('rv_') . '_' . $name;

        file_put_contents($path, $contents ?? str_repeat('0', $kilobytes * 1024));

        return new UploadedFile($path, $name, $mime, null, true);
    }

    /**
     * A real PNG of roughly the requested size.
     *
     * The pixels are noise on purpose: a solid colour compresses to almost
     * nothing however large the image, so a plain one cannot be used to
     * test a ceiling measured in megabytes.
     */
    private function fakeLargePng(string $name, int $targetKilobytes): UploadedFile
    {
        // Three bytes a pixel, plus a filter byte per row; noise does not
        // compress, so the file lands near the raw size.
        $side = (int) ceil(sqrt(($targetKilobytes * 1024) / 3));

        $rows = '';
        for ($y = 0; $y < $side; $y++) {
            $rows .= "\x00" . random_bytes($side * 3);
        }

        $png  = "\x89PNG\r\n\x1a\n";
        $png .= $this->pngChunk('IHDR', pack('NNCCCCC', $side, $side, 8, 2, 0, 0, 0));
        $png .= $this->pngChunk('IDAT', gzcompress($rows, 1));
        $png .= $this->pngChunk('IEND', '');

        return $this->fileOf($name, 0, 'image/png', $png);
    }

    /** Enough of an MP4 for the server to recognise one. */
    private function fakeMp4(string $name, int $kilobytes): UploadedFile
    {
        $header = "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom";

        return $this->fileOf($name, $kilobytes, 'video/mp4',
            $header . str_repeat("\x00", max(0, $kilobytes * 1024 - strlen($header))));
    }

    private function submit(User $user, Product $product, array $payload)
    {
        return $this->actingAs($user, 'web')
            ->post("/shop-api/products/{$product->product_id}/reviews", $payload + ['rating' => 5]);
    }

    // ------------------------------------------------------------- accepting

    #[Test]
    public function a_photograph_can_be_attached_to_a_review(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        $this->submit($user, $product, ['media' => [$this->fakePng('bottle.png', 600, 600)]])
            ->assertOk();

        $media = ProductReviewMedia::first();

        $this->assertNotNull($media, 'The photograph was not kept.');
        $this->assertSame(ProductReviewMedia::KIND_PHOTO, $media->kind);
        Storage::disk('public')->assertExists($media->path);
    }

    #[Test]
    public function a_short_video_can_be_attached(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        $this->submit($user, $product, ['media' => [$this->fakeMp4('clip.mp4', 500)]])
            ->assertOk();

        $this->assertSame(ProductReviewMedia::KIND_VIDEO, ProductReviewMedia::first()->kind);
    }

    #[Test]
    public function a_review_still_works_with_nothing_attached(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        $this->submit($user, $product, ['body' => 'Good oil, nothing to show.'])->assertOk();

        $this->assertSame(1, ProductReview::count());
        $this->assertSame(0, ProductReviewMedia::count());
    }

    // ------------------------------------------------------------ the whitelist

    /**
     * The thing that actually matters: a stranger uploads this and other
     * customers' browsers then open it.
     */
    #[Test]
    public function a_file_that_is_not_a_picture_or_a_clip_is_refused(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        foreach ([
            ['evil.php', 'text/x-php'],
            ['notes.txt', 'text/plain'],
            ['sheet.pdf', 'application/pdf'],
            ['archive.zip', 'application/zip'],
            ['script.js', 'application/javascript'],
        ] as [$name, $mime]) {
            $this->submit($user, $product, ['media' => [$this->fileOf($name, 10, $mime)]])
                ->assertStatus(302);          // a non-JSON post redirects back with errors

            $this->assertSame(0, ProductReviewMedia::count(), "{$name} was accepted.");
            $this->assertSame(0, ProductReview::count(), "{$name} left a review behind.");
        }
    }

    /**
     * Renaming a script to .png must not get it past. The rule reads the
     * file, not its name.
     */
    #[Test]
    public function a_script_wearing_a_picture_name_is_refused(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        $disguised = $this->fileOf('holiday.png', 1, 'image/png', 'This is not a picture. It is text wearing a picture name.');

        $this->submit($user, $product, ['media' => [$disguised]])->assertStatus(302);

        $this->assertSame(0, ProductReviewMedia::count(), 'A PHP file was stored as a photograph.');
    }

    // --------------------------------------------------------------- the size

    #[Test]
    public function a_photograph_over_its_ceiling_is_refused(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        $limit = (int) config('reviews.media.photo.max_kilobytes');

        $this->submit($user, $product, ['media' => [$this->fileOf('huge.jpg', $limit + 512, 'image/jpeg')]])
            ->assertStatus(302);

        $this->assertSame(0, ProductReviewMedia::count());
    }

    /**
     * The point of two ceilings. A photograph the size of a permitted video
     * must still be refused, or the larger limit silently becomes the only
     * limit.
     */
    #[Test]
    public function a_video_sized_photograph_is_still_refused(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        $photoLimit = (int) config('reviews.media.photo.max_kilobytes');
        $videoLimit = (int) config('reviews.media.video.max_kilobytes');

        $this->assertGreaterThan($photoLimit, $videoLimit, 'The two ceilings are the same, so this proves nothing.');

        // Comfortably inside the video ceiling, well over the photo one.
        $between = $photoLimit + 1024;

        $this->submit($user, $product, ['media' => [$this->fakeLargePng('big.png', $between)]])
            ->assertStatus(422);

        $this->assertSame(0, ProductReviewMedia::count(), 'A photograph was allowed a video\'s size.');
    }

    #[Test]
    public function more_files_than_allowed_are_refused(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        $max = (int) config('reviews.media.max_files');

        $files = [];
        for ($i = 0; $i <= $max; $i++) {
            $files[] = $this->fakePng("photo{$i}.png", 300, 300);
        }

        $this->submit($user, $product, ['media' => $files])->assertStatus(302);

        $this->assertSame(0, ProductReviewMedia::count());
    }

    #[Test]
    public function the_most_allowed_is_accepted(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        $max = (int) config('reviews.media.max_files');

        $files = [];
        for ($i = 0; $i < $max; $i++) {
            $files[] = $this->fakePng("photo{$i}.png", 300, 300);
        }

        $this->submit($user, $product, ['media' => $files])->assertOk();

        $this->assertSame($max, ProductReviewMedia::count());
    }

    // ----------------------------------------------------------- what is shown

    #[Test]
    public function the_shop_is_told_where_to_find_them(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        $this->submit($user, $product, ['media' => [$this->fakePng('bottle.png', 400, 400)]])->assertOk();

        $row = $this->getJson("/shop-api/products/{$product->product_id}/reviews")
            ->assertOk()
            ->json('data.0.media.0');

        $this->assertSame('photo', $row['kind']);
        // Root-relative, so it is right on localhost and behind a tunnel
        // alike. An address built here would carry whichever host built it.
        $this->assertStringStartsWith('/storage/', $row['url']);
    }

    #[Test]
    public function the_business_sees_them_when_it_moderates(): void
    {
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product();
        $this->delivered($user, $product);

        $this->submit($user, $product, ['media' => [$this->fakePng('bottle.png', 400, 400)]])->assertOk();

        $admin = User::create([
            'email' => 'boss@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Ana Reyes',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $media = $this->actingAs($admin, 'staff')
            ->getJson('/admin-api/reviews')
            ->assertOk()
            ->json('data.0.media');

        $this->assertCount(1, $media, 'Moderation cannot see what was attached.');
    }

    // ---------------------------------------------------------- the settings

    #[Test]
    public function the_limits_are_settings_rather_than_numbers_in_the_code(): void
    {
        $media = config('reviews.media');

        $this->assertIsInt($media['max_files']);
        $this->assertNotEmpty($media['photo']['mimes']);
        $this->assertNotEmpty($media['video']['mimes']);

        // Nothing executable may be on either list, however the lists change.
        foreach (['php', 'phtml', 'js', 'html', 'htm', 'svg', 'exe', 'sh', 'bat'] as $dangerous) {
            $this->assertNotContains($dangerous, $media['photo']['mimes'], "{$dangerous} is on the photo list.");
            $this->assertNotContains($dangerous, $media['video']['mimes'], "{$dangerous} is on the video list.");
        }
    }

    /**
     * PHP refuses an upload over its own ceiling before any of this runs,
     * and the request arrives empty with nothing to explain it.
     */
    #[Test]
    public function the_ceilings_sit_under_what_php_will_accept(): void
    {
        $toKb = function (string $value): int {
            $unit = strtolower(substr(trim($value), -1));
            $n = (int) $value;

            return match ($unit) {
                'g' => $n * 1024 * 1024,
                'm' => $n * 1024,
                'k' => $n,
                default => (int) ($n / 1024),
            };
        };

        $php = min($toKb(ini_get('upload_max_filesize')), $toKb(ini_get('post_max_size')));

        foreach (['photo', 'video'] as $kind) {
            $this->assertLessThanOrEqual(
                $php,
                (int) config("reviews.media.{$kind}.max_kilobytes"),
                "The {$kind} ceiling is above what PHP will accept, so files under it are still refused by the server."
            );
        }
    }

    #[Test]
    public function the_page_states_the_limits_rather_than_hiding_them(): void
    {
        $view = file_get_contents(resource_path('views/customer/product-details.blade.php'));

        $this->assertStringContainsString("config('reviews.media')", $view);
        $this->assertStringContainsString('max_kilobytes', $view);
        $this->assertStringContainsString('id="reviewMedia"', $view);
    }
}
