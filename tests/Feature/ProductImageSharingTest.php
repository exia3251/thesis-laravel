<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\MakesImages;
use Tests\TestCase;

/**
 * Changing the picture of an oil that comes in more than one size.
 *
 * The packs of one oil share a picture: the catalogue seeder writes the same
 * path to every pack of a line, because a litre and a drum of the same oil
 * are the same bottle in two sizes. The shop then shows one card per line and
 * reads its picture off whichever pack has one.
 *
 * Editing a single pack therefore used to do two wrong things at once. It
 * deleted the file every pack of the line pointed at, and it wrote the new
 * path to only the pack being edited -- so the shop, reading a different
 * pack, was left pointing at a file that no longer existed. The back office
 * showed the new picture on the row that had been edited, so the change
 * looked like it had worked and only the storefront was broken.
 */
class ProductImageSharingTest extends TestCase
{
    use RefreshDatabase;
    use MakesImages;

    private function admin(): User
    {
        return User::create([
            'email' => 'boss@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Ana Reyes',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    /** Two packs of one oil, sharing a picture as the seeder leaves them. */
    private function packsSharingAnImage(string $path = 'products/shared-oil.png'): array
    {
        Storage::disk('public')->put($path, $this->pngBytes(10, 10));

        $packs = [];

        foreach (['1L', '4L'] as $unit) {
            $product = Product::create([
                'product_name' => 'Shared Oil 5W30',
                'product_line' => 'shared-oil-5w30',
                'brand' => 'SOLAR',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W30',
                'unit' => $unit,
                'price' => $unit === '1L' ? 450 : 1600,
                'reorder_level' => 5,
                'image_path' => $path,
            ]);

            Inventory::create(['product_id' => $product->product_id, 'quantity' => 20]);

            $packs[$unit] = $product;
        }

        return $packs;
    }

    /** The fields the edit form always posts back. */
    private function fieldsFor(Product $product): array
    {
        return [
            'product_name'    => $product->product_name,
            'brand'           => $product->brand,
            'product_line'    => $product->product_line,
            'oil_type'        => $product->oil_type,
            'viscosity_grade' => $product->viscosity_grade,
            'unit'            => $product->unit,
            'price'           => (int) $product->price,
            'reorder_level'   => $product->reorder_level,
        ];
    }

    #[Test]
    public function a_new_picture_reaches_every_pack_of_the_line(): void
    {
        Storage::fake('public');

        $packs = $this->packsSharingAnImage();
        $old = $packs['1L']->image_path;

        $this->actingAs($this->admin(), 'staff')
            ->post("/admin-api/products/{$packs['4L']->product_id}", $this->fieldsFor($packs['4L']) + [
                'image' => $this->fakePng('new.png', 20, 20),
            ])
            ->assertOk();

        $new = $packs['4L']->fresh()->image_path;

        $this->assertNotSame($old, $new, 'The picture was not replaced at all.');
        $this->assertSame(
            $new,
            $packs['1L']->fresh()->image_path,
            'The other pack of the same oil was left pointing at the old picture.'
        );
    }

    /**
     * The storefront is where the fault showed, so this asserts what the
     * storefront would actually be handed.
     */
    #[Test]
    public function the_shop_card_still_has_a_picture_afterwards(): void
    {
        Storage::fake('public');

        $packs = $this->packsSharingAnImage();

        $this->actingAs($this->admin(), 'staff')
            ->post("/admin-api/products/{$packs['4L']->product_id}", $this->fieldsFor($packs['4L']) + [
                'image' => $this->fakePng('new.png', 20, 20),
            ])
            ->assertOk();

        $card = collect($this->getJson('/shop-api/products')->assertOk()->json('data'))
            ->firstWhere('product_line', 'shared-oil-5w30');

        $this->assertNotNull($card, 'The oil fell off the shop entirely.');
        $this->assertNotNull($card['image_url'], 'The shop card lost its picture.');

        // And the file it names is really there.
        $path = str_replace(asset('storage') . '/', '', $card['image_url']);
        Storage::disk('public')->assertExists($path);
    }

    /**
     * Deleting on sight took the picture away from the pack's siblings. A
     * file is only removed once nothing points at it.
     */
    #[Test]
    public function a_picture_another_pack_still_uses_is_not_deleted(): void
    {
        Storage::fake('public');

        $packs = $this->packsSharingAnImage();
        $shared = $packs['1L']->image_path;

        // Only this pack moves to a new picture; the sibling keeps the old
        // path, which is the situation the guard exists for.
        $packs['4L']->forceFill(['product_line' => 'moved-away'])->save();

        $this->actingAs($this->admin(), 'staff')
            ->post("/admin-api/products/{$packs['4L']->product_id}", [
                'product_name'    => $packs['4L']->product_name,
                'brand'           => $packs['4L']->brand,
                'product_line'    => 'moved-away',
                'oil_type'        => $packs['4L']->oil_type,
                'viscosity_grade' => $packs['4L']->viscosity_grade,
                'unit'            => $packs['4L']->unit,
                'price'           => (int) $packs['4L']->price,
                'reorder_level'   => $packs['4L']->reorder_level,
                'image' => $this->fakePng('new.png', 20, 20),
            ])
            ->assertOk();

        Storage::disk('public')->assertExists($shared);
        $this->assertSame($shared, $packs['1L']->fresh()->image_path);
    }

    #[Test]
    public function a_picture_nothing_points_at_any_more_is_cleaned_up(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'product_name' => 'Only Pack 10W40',
            'product_line' => 'only-pack-10w40',
            'brand' => 'SOLAR',
            'oil_type' => 'Synthetic',
            'viscosity_grade' => '10W40',
            'unit' => '1L',
            'price' => 420,
            'reorder_level' => 5,
            'image_path' => 'products/lonely.png',
        ]);
        Inventory::create(['product_id' => $product->product_id, 'quantity' => 10]);
        Storage::disk('public')->put('products/lonely.png', $this->pngBytes(10, 10));

        $this->actingAs($this->admin(), 'staff')
            ->post("/admin-api/products/{$product->product_id}", $this->fieldsFor($product) + [
                'image' => $this->fakePng('new.png', 20, 20),
            ])
            ->assertOk();

        Storage::disk('public')->assertMissing('products/lonely.png');
    }
}
