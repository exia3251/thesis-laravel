<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What a search box does with what people actually type.
 *
 * Every one of them wrapped the term in per cent signs and asked for rows
 * containing that exact string, which reads like it works and failed on the
 * two commonest queries in this shop.
 *
 * "5W-30" found nothing, because the column holds 5W30 -- and the hyphen is
 * there on the bottle, in the handbook and in this system's own chat replies,
 * so it is how everybody writes it. "motor oil" found nothing either, because
 * the products are called "Solar Premium Series Motor Engine Oil" and no
 * single substring spans the word in between.
 */
class SearchMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stock('Solar Premium Series Motor Engine Oil 5W30 API SN/CF', 'SOLAR', '5W30');
        $this->stock('Canroyal Full Synthetic Diesel Engine Oil SAE 15W40 API CI-4', 'CANROYAL', '15W40');
        $this->stock('Solar Antifreeze Coolant Green', 'SOLAR', null);
    }

    private function stock(string $name, string $brand, ?string $grade): void
    {
        Product::create([
            'product_name' => $name,
            'brand' => $brand,
            'product_line' => str($name)->lower()->slug(),
            'oil_type' => $grade ? 'Synthetic' : 'Coolant',
            'viscosity_grade' => $grade,
            'unit' => '1 Liter',
            'price' => 500,
            'reorder_level' => 10,
        ]);
    }

    private function find(string $term): int
    {
        return Search::apply(
            Product::query(),
            $term,
            ['product_name', 'brand', 'oil_type', 'viscosity_grade']
        )->count();
    }

    #[Test]
    public function a_grade_is_found_however_it_is_punctuated(): void
    {
        // The column holds 5W30. All three of these are the same grade.
        $this->assertSame(1, $this->find('5W30'));
        $this->assertSame(1, $this->find('5W-30'));
        $this->assertSame(1, $this->find('5w 30'));
    }

    #[Test]
    public function words_may_be_separated_in_the_name(): void
    {
        // "Motor Engine Oil" -- the query skips the word in the middle.
        $this->assertSame(1, $this->find('motor oil'));
    }

    #[Test]
    public function every_word_has_to_land_somewhere(): void
    {
        // Narrowing, not widening: the second word rules rows out.
        $this->assertSame(2, $this->find('solar'));
        $this->assertSame(1, $this->find('solar 5w30'));
        $this->assertSame(0, $this->find('solar 15w40'));
    }

    #[Test]
    public function the_words_may_come_from_different_columns(): void
    {
        // "canroyal" is the brand, "diesel" is in the name.
        $this->assertSame(1, $this->find('canroyal diesel'));
    }

    #[Test]
    public function nothing_matching_finds_nothing(): void
    {
        $this->assertSame(0, $this->find('bananas'));
    }

    #[Test]
    public function an_empty_term_leaves_the_list_alone(): void
    {
        $this->assertSame(3, $this->find(''));
        $this->assertSame(3, $this->find('   '));
    }

    #[Test]
    public function the_in_memory_rule_agrees_with_the_database_one(): void
    {
        // The browser filters some of these lists itself, and a shopper typing
        // into the shop should get what a member of staff typing the same
        // thing into Inventory gets.
        $fields = ['Solar Premium Series Motor Engine Oil 5W30 API SN/CF', 'SOLAR', 'Synthetic', '5W30'];

        foreach (['5W30', '5W-30', '5w 30', 'motor oil', 'solar 5w30'] as $term) {
            $this->assertTrue(Search::matches($term, $fields), $term);
        }

        foreach (['bananas', 'solar 15w40'] as $term) {
            $this->assertFalse(Search::matches($term, $fields), $term);
        }
    }
}
