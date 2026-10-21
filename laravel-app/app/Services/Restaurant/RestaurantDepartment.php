<?php

namespace App\Services\Restaurant;

use App\Category;

class RestaurantDepartment
{
    public static function normalize($value)
    {
        $value = strtolower(trim((string) $value));
        if ($value === 'food' || $value === 'drinks') {
            return $value;
        }

        return null;
    }

    public static function forCategoryId($categoryId)
    {
        foreach (self::chain($categoryId) as $category) {
            $department = self::normalize($category->department);
            if ($department) {
                return $department;
            }
        }

        return null;
    }

    /**
     * coffee, restaurant, or bar. Laundry and every other category return null.
     * A child follows its parent, so a subcategory under Food stays Restaurant
     * and a subcategory under Coffee & Tea stays Coffee & Tea.
     */
    public static function laneForCategoryId($categoryId)
    {
        $chain = self::chain($categoryId);
        if (! $chain) {
            return null;
        }
        foreach ($chain as $category) {
            $service = strtolower(trim((string) ($category->service ?? '')));
            $name = strtolower(trim((string) $category->name));
            if ($service === 'laundry' || $name === 'laundry') {
                return null;
            }
        }
        foreach ($chain as $category) {
            $service = strtolower(trim((string) ($category->service ?? '')));
            $name = strtolower(trim((string) $category->name));
            if ($service === 'coffee') {
                return 'coffee';
            }
            if ($service === 'restaurant' || $name === 'food' || $name === 'restaurant') {
                return 'restaurant';
            }
            if ($name === 'drinks' || $name === 'bar') {
                return 'bar';
            }
        }
        foreach ($chain as $category) {
            $department = self::normalize($category->department);
            if ($department === 'food') {
                return 'restaurant';
            }
            if ($department === 'drinks') {
                return 'bar';
            }
        }

        return null;
    }

    public static function label($lane)
    {
        $map = [
            'coffee' => 'Coffee & Tea',
            'restaurant' => 'Restaurant',
            'bar' => 'Bar',
            'online' => 'Online order',
            'food' => 'Restaurant',
            'drinks' => 'Bar',
        ];

        return isset($map[$lane]) ? $map[$lane] : ucfirst((string) $lane);
    }

    protected static function chain($categoryId)
    {
        $chain = [];
        $seen = [];
        $guard = 0;
        while ($categoryId && $guard < 12) {
            $guard++;
            $id = (int) $categoryId;
            if ($id < 1 || isset($seen[$id])) {
                break;
            }
            $seen[$id] = true;
            $category = Category::find($id);
            if (! $category) {
                break;
            }
            $chain[] = $category;
            $categoryId = $category->parent_id;
        }

        return $chain;
    }
}
