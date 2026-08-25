<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CafeMenuSeeder extends Seeder
{
    public function run()
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('products') || ! Schema::hasTable('units')) {
            $this->command && $this->command->warn('Cafe menu seed skipped — products tables are not migrated yet.');
            return;
        }

        $now = now();
        $unitId = $this->ensureUnit($now);

        $catalog = [
            'Coffee & Tea' => [
                ['Espresso', 'Double espresso', 2000],
                ['Americano', 'Espresso, hot water', 2000],
                ['Macchiato', 'Espresso, foam', 2000],
                ['Cortado', 'Espresso, milk, foam', 2000],
                ['Flat White', 'Espresso, less milk, few foam', 2500],
                ['Cappuccino', 'Espresso, less milk, foam', 3000],
                ['Café Latte', 'Espresso, milk', 3000],
                ['Caramel Macchiato', 'Caramel, espresso, milk, foam', 4000],
                ['Café Mocha', 'Chocolate, espresso, milk', 4000],
                ['African Coffee', 'Chocolate, espresso, ginger, milk', 4000],
            ],
            'Iced & Specialty' => [
                ['Ice Americano', 'Ice, mineral water', 4000],
                ['Ice Café Latte', 'Ice, milk, espresso', 4000],
                ['Ice Cappuccino', 'Ice, milk, double espresso, foam', 4000],
                ['Ice Mocha', 'Iced mocha', 5000],
                ['Ice White Mocha', 'Iced white mocha', 5000],
                ['Ice Chocolate', 'Iced chocolate', 4000],
                ['Ice Vanilla Latte', 'Iced vanilla latte', 5000],
                ['Ice Caramel Macchiato', 'Iced caramel macchiato', 5000],
                ['Ice African Coffee', 'Iced African coffee', 5000],
                ['Ice Brewed Coffee', 'Guest brewing method preference', 4000],
                ['V60', 'Pour over', 2000],
                ['Chemex', 'Chemex brew', 4000],
                ['French Press', 'French press', 3000],
                ['Clever Dripper', 'Clever dripper', 3000],
                ['Syphon Brew', 'Syphon brew', 3000],
                ['Mocha Port Coffee', 'Mocha pot', 3000],
                ['Ethiopian Coffee Ceremony', 'Traditional ceremony', 30000],
            ],
            'Tea & Hot Beverages' => [
                ['Steamed Milk', 'Hot steamed milk', 2000],
                ['African Tea', 'Ginger, tea, milk', 3000],
                ['Hot Chocolate', 'Drinking chocolate, hot milk', 3000],
                ['Spiced Tea', 'Honey, lemon, ginger, tea', 3000],
                ['Lemon Green Tea', 'Lemon, green tea', 3000],
                ['Black Tea', 'Black tea', 2000],
                ['Lemon Tea', 'Lemon tea', 3000],
                ['Ginger Tea', 'Ginger tea', 3000],
                ['Milk Tea', 'Tea with milk', 2500],
                ['Choose Flavored Tea', 'Cinnamon, Earl Grey, chamomile, Masala, clover, peppermint, chai, hibiscus', 4000],
            ],
            'Fresh & Detox Juices' => [
                ['Passion', 'Fresh passion juice', 4000],
                ['Pineapple', 'Fresh pineapple juice', 4000],
                ['Three Tomato', 'Fresh tomato blend', 5000],
                ['Ginger Mint Detox', 'Ginger, mint detox', 5000],
                ['Beetroot Detox', 'Beetroot detox blend', 5000],
            ],
            'Smoothies' => [
                ['Mango Delight', 'Mango smoothie', 5000],
                ['Strawberry Banana', 'Strawberry banana smoothie', 5000],
                ['Tropical Paradise', 'Tropical fruit smoothie', 5000],
                ['Berry Blast', 'Berry smoothie', 5000],
                ['Banana Peanut Butter', 'Banana peanut butter smoothie', 5000],
                ['Avocado Smoothie', 'Avocado smoothie', 5000],
                ['Chocolate Banana', 'Chocolate banana smoothie', 5000],
                ['Green Detox', 'Green detox smoothie', 5000],
                ['Pina Colada', 'Pina colada smoothie', 5000],
            ],
            'Food' => [],
        ];

        $seq = 1;
        foreach ($catalog as $categoryName => $items) {
            $categoryId = $this->ensureCategory($categoryName, $now);
            foreach ($items as $item) {
                $this->ensureProduct($item[0], $item[1], $item[2], $categoryId, $unitId, $seq, $now);
                $seq++;
            }
        }
    }

    private function ensureUnit($now)
    {
        $existing = DB::table('units')->where('unit_code', 'PCS')->first();
        if ($existing) {
            return $existing->id;
        }

        return DB::table('units')->insertGetId([
            'unit_code' => 'PCS',
            'unit_name' => 'Portion',
            'base_unit' => null,
            'operator' => '*',
            'operation_value' => 1,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureCategory($name, $now)
    {
        $existing = DB::table('categories')->where('name', $name)->first();
        if ($existing) {
            return $existing->id;
        }

        return DB::table('categories')->insertGetId([
            'name' => $name,
            'parent_id' => 0,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureProduct($name, $details, $price, $categoryId, $unitId, $seq, $now)
    {
        $code = 'W2K-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
        $existing = DB::table('products')->where('name', $name)->where('category_id', $categoryId)->first();
        if ($existing) {
            return $existing->id;
        }

        $row = [
            'name' => $name,
            'code' => $code,
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'brand_id' => null,
            'category_id' => $categoryId,
            'unit_id' => $unitId,
            'purchase_unit_id' => $unitId,
            'sale_unit_id' => $unitId,
            'cost' => (string) $price,
            'price' => (string) $price,
            'qty' => 0,
            'alert_quantity' => 0,
            'tax_method' => 1,
            'product_details' => $details,
            'is_active' => 1,
            'featured' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return DB::table('products')->insertGetId($row);
    }
}
