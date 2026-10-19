<?php

namespace App\Support;

use App\Category;
use App\Product;
use Illuminate\Support\Facades\Schema;

class ServiceCatalog
{
    const SERVICES = [
        'supermarket' => ['icon' => 'shopping-basket', 'priority' => true],
        'restaurant' => ['icon' => 'utensils-crossed', 'image' => 'branding/menu/iced-specialty.jpg'],
        'coffee' => ['icon' => 'coffee', 'image' => 'branding/menu/coffee-tea.jpg'],
        'laundry' => ['icon' => 'shirt'],
        'fashion' => ['icon' => 'shopping-bag'],
    ];

    const KEYWORDS = [
        'laundry' => ['laundry', 'dry clean', 'ironing', 'washing'],
        'fashion' => ['fashion', 'cloth', 'shoe', 'dress', 'shirt', 'trouser', 'accessor', 'jewel', 'bag'],
        'restaurant' => ['restaurant', 'food', 'steak', 'meat', 'chicken', 'fish', 'meal', 'breakfast', 'lunch', 'dinner', 'starter', 'main', 'dessert', 'grill', 'pizza', 'burger', 'bar', 'beer', 'wine', 'cocktail', 'spirit', 'whisk', 'liquor'],
        'coffee' => ['coffee', 'espresso', 'tea', 'iced', 'specialty', 'juice', 'smoothie', 'pastr', 'hot beverage'],
        'supermarket' => ['supermarket', 'grocer', 'snack', 'soft drink', 'water', 'toiletr', 'household', 'cleaning', 'dairy', 'bakery', 'canned', 'cereal', 'convenience'],
    ];

    const CATEGORY_ICONS = [
        'laundry' => 'washing-machine', 'iron' => 'shirt', 'dry clean' => 'sparkles',
        'coffee' => 'coffee', 'espresso' => 'coffee', 'iced' => 'glass-water', 'tea' => 'cup-soda',
        'juice' => 'citrus', 'smoothie' => 'milk', 'food' => 'utensils', 'breakfast' => 'egg-fried',
        'pizza' => 'pizza', 'burger' => 'sandwich', 'dessert' => 'cake-slice', 'pastr' => 'croissant',
        'beer' => 'beer', 'wine' => 'wine', 'cocktail' => 'martini', 'spirit' => 'martini', 'bar' => 'wine',
        'snack' => 'cookie', 'drink' => 'cup-soda', 'water' => 'glass-water', 'dairy' => 'milk',
        'bakery' => 'croissant', 'fruit' => 'apple', 'vegetable' => 'carrot', 'toiletr' => 'bath',
        'household' => 'house', 'cleaning' => 'spray-can', 'shoe' => 'footprints', 'bag' => 'shopping-bag',
        'jewel' => 'gem', 'accessor' => 'watch', 'cloth' => 'shirt', 'dress' => 'shirt', 'shirt' => 'shirt',
    ];

    public static function keys()
    {
        return array_keys(self::SERVICES);
    }

    public static function exists($key)
    {
        return isset(self::SERVICES[$key]);
    }

    public static function enabled()
    {
        return Schema::hasTable('categories') && Schema::hasColumn('categories', 'service');
    }

    public static function guessService($name)
    {
        $name = ' '.strtolower((string) $name).' ';
        foreach (self::KEYWORDS as $service => $words) {
            foreach ($words as $word) {
                if (strpos($name, $word) !== false) {
                    return $service;
                }
            }
        }

        return null;
    }

    public static function imageUrl($key)
    {
        $image = self::SERVICES[$key]['image'] ?? null;

        return $image && is_file(public_path($image)) ? asset('public/'.$image).'?v=3' : null;
    }

    public static function categoryIcon($name)
    {
        $name = strtolower((string) $name);
        foreach (self::CATEGORY_ICONS as $word => $icon) {
            if (strpos($name, $word) !== false) {
                return $icon;
            }
        }

        return 'tag';
    }

    public static function categoryImage($category)
    {
        $filename = trim((string) ($category->image ?? ''));
        if ($filename === '' || strtolower($filename) === 'null' || $filename === 'zummXD2dvAtI.png') {
            return null;
        }
        $path = public_path('images/category/'.PosProductImage::firstName($filename));

        return is_file($path) && filesize($path) > 0 ? PosProductImage::catalogUrl('category', $filename) : null;
    }

    public static function categories($key)
    {
        if (! self::enabled()) {
            return collect();
        }

        return Category::where('is_active', true)
            ->where('service', $key)
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => self::categoryIcon($category->name),
                    'image' => self::categoryImage($category),
                    'count' => Product::where('category_id', $category->id)->where('is_active', true)->count(),
                ];
            })
            ->values();
    }

    public static function items($categoryId)
    {
        return Product::where('category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                $filename = PosProductImage::firstName($product->image);
                $path = public_path('images/product/'.$filename);
                $hasImage = $filename !== 'zummXD2dvAtI.png' && is_file($path) && filesize($path) > 0;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'details' => trim(strip_tags((string) $product->product_details)),
                    'image' => $hasImage ? PosProductImage::url($filename) : null,
                    'price' => (int) $product->price,
                ];
            })
            ->values();
    }
}
