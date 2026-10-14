<?php

namespace App\Support;

use App\Category;
use App\Product;
use App\Unit;

class LaundryCatalog
{
    public static function categoryNames()
    {
        return ['laundry', 'services', 'service'];
    }

    public static function category()
    {
        $named = Category::where('name', 'Laundry')->first();
        if ($named) {
            return $named;
        }

        return Category::whereRaw('LOWER(TRIM(name)) IN (?, ?, ?)', self::categoryNames())->first();
    }

    public static function categoryIds()
    {
        static $ids;
        if ($ids !== null) {
            return $ids;
        }
        $ids = Category::whereRaw('LOWER(TRIM(name)) IN (?, ?, ?)', self::categoryNames())
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();

        return $ids;
    }

    public static function unit()
    {
        return Unit::where('unit_code', 'laundry')->first()
            ?: Unit::where('unit_code', 'service')->first();
    }

    public static function isLaundryProduct($product)
    {
        if (! $product) {
            return false;
        }
        $type = strtolower((string) ($product->type ?? ''));
        if ($type === 'laundry') {
            return true;
        }
        $catId = (int) ($product->category_id ?? 0);

        return $catId > 0 && in_array($catId, self::categoryIds(), true);
    }

    public static function queryProducts()
    {
        $ids = self::categoryIds();

        return Product::where('is_active', 1)->where(function ($q) use ($ids) {
            $q->where('type', 'laundry');
            if ($ids) {
                $q->orWhereIn('category_id', $ids);
            }
        })->orderBy('name');
    }
}
