<?php

namespace App\Support;

use App\Category;
use App\Product;
use Illuminate\Support\Facades\Schema;

class ServiceCatalog
{
    const SERVICES = [
        'supermarket' => ['icon' => 'shopping-basket', 'priority' => true],
        'restaurant' => ['icon' => 'utensils-crossed'],
        'coffee' => ['icon' => 'coffee', 'image' => 'branding/menu/coffee-tea.jpg'],
        'laundry' => ['icon' => 'shirt'],
        'fashion' => ['icon' => 'shopping-bag'],
    ];

    const KEYWORDS = [
        'laundry' => ['laundry', 'dry clean', 'ironing', 'washing'],
        'fashion' => ['fashion', 'cloth', 'shoe', 'dress', 'shirt', 'trouser', 'accessor', 'jewel', 'bag', 'hugo'],
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
        'fashion' => 'shopping-bag', 'hugo' => 'gem', 'suit' => 'shirt', 'trouser' => 'shirt',
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

    /** Card photo: the service's own category image (restaurant uses its food photo). */
    public static function heroUrl($key)
    {
        $tree = self::categories($key);
        if ($key === 'restaurant') {
            foreach ($tree as $node) {
                foreach ($node['children'] ?? [] as $child) {
                    if (stripos((string) $child['name'], 'food') !== false && ! empty($child['image'])) {
                        return $child['image'];
                    }
                }
                if (stripos((string) $node['name'], 'food') !== false && ! empty($node['image'])) {
                    return $node['image'];
                }
                if (! empty($node['image'])) {
                    return $node['image'];
                }
            }
        }
        foreach ($tree as $node) {
            if (! empty($node['image'])) {
                return $node['image'];
            }
        }

        return self::imageUrl($key);
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

    public static function parentId($category)
    {
        return (int) ($category->parent_id ?: 0);
    }

    /**
     * Parent categories for a service (roots). Each may include nested children.
     */
    public static function categories($key)
    {
        return self::categoryTree($key);
    }

    public static function categoryTree($key)
    {
        if (! self::enabled()) {
            return collect();
        }

        $all = Category::where('is_active', true)
            ->where('service', $key)
            ->orderBy('name')
            ->get();

        if ($all->isEmpty()) {
            return collect();
        }

        // Pull in child categories that were never tagged with this service.
        $ids = $all->pluck('id')->all();
        $extra = Category::where('is_active', true)
            ->whereIn('parent_id', $ids)
            ->whereNotIn('id', $ids)
            ->where(function ($query) use ($key) {
                $query->whereNull('service')->orWhere('service', '')->orWhere('service', $key);
            })
            ->orderBy('name')
            ->get();
        if ($extra->isNotEmpty()) {
            $all = $all->concat($extra)->unique('id')->sortBy('name')->values();
        }

        $byId = $all->keyBy('id');
        $childrenMap = [];
        foreach ($all as $category) {
            $pid = self::parentId($category);
            // Ignore self-parent rows (legacy bad data) so the tree can still root.
            if ($pid > 0 && $pid !== (int) $category->id && $byId->has($pid)) {
                $childrenMap[$pid][] = $category->id;
            }
        }

        $productCounts = self::directProductCounts($all->pluck('id')->all());

        $build = function ($category) use (&$build, $byId, $childrenMap, $productCounts) {
            $childIds = $childrenMap[$category->id] ?? [];
            $children = collect($childIds)->map(function ($id) use ($byId, &$build) {
                return $build($byId->get($id));
            })->filter()->values();

            $own = (int) ($productCounts[$category->id] ?? 0);
            $count = $own + $children->sum('count');

            return [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => self::categoryIcon($category->name),
                'image' => self::categoryImage($category),
                'count' => $count,
                'own_count' => $own,
                'has_children' => $children->isNotEmpty(),
                'children' => $children,
            ];
        };

        return $all->filter(function ($category) use ($byId) {
            $pid = self::parentId($category);
            if ($pid === 0 || $pid === (int) $category->id) {
                return true;
            }

            return ! $byId->has($pid);
        })->map(function ($category) use ($build) {
            return $build($category);
        })->values();
    }

    /**
     * What Browse should list: subcategories under a single wrapper,
     * otherwise the parent categories themselves.
     */
    public static function browseNodes($tree)
    {
        $tree = collect($tree);
        if ($tree->count() === 1 && ! empty($tree[0]['has_children'])) {
            $root = $tree[0];
            $nodes = collect($root['children'] ?? [])->values();
            if ((int) ($root['own_count'] ?? 0) > 0) {
                $self = $root;
                $self['items_only'] = true;
                $self['has_children'] = false;
                $self['count'] = (int) $root['own_count'];
                $self['children'] = collect();
                $nodes->prepend($self);
            }

            return $nodes;
        }

        return $tree->values();
    }

    public static function findInTree($tree, $categoryId)
    {
        $categoryId = (int) $categoryId;
        foreach ($tree as $node) {
            if ((int) $node['id'] === $categoryId) {
                return $node;
            }
            $found = self::findInTree($node['children'] ?? collect(), $categoryId);
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    public static function breadcrumb($tree, $categoryId)
    {
        $path = [];
        $walk = function ($nodes, $trail) use (&$walk, &$path, $categoryId) {
            foreach ($nodes as $node) {
                $next = array_merge($trail, [$node]);
                if ((int) $node['id'] === (int) $categoryId) {
                    $path = $next;

                    return true;
                }
                if (! empty($node['children']) && $walk($node['children'], $next)) {
                    return true;
                }
            }

            return false;
        };
        $walk($tree, []);

        return $path;
    }

    public static function items($categoryId, $includeChildren = false)
    {
        $ids = [(int) $categoryId];
        if ($includeChildren && self::enabled()) {
            $ids = array_merge($ids, self::descendantIds((int) $categoryId));
        }

        return Product::whereIn('category_id', $ids)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'details' => trim(strip_tags((string) $product->product_details)),
                    'image' => PosProductImage::photoUrl($product->image),
                    'icon' => self::categoryIcon($product->name),
                    'price' => (int) $product->price,
                ];
            })
            ->values();
    }

    /** Preview products for a service card (e.g. Laundry item list). */
    public static function previewItems($key, $limit = 12)
    {
        if (! self::enabled()) {
            return collect();
        }

        $categoryIds = Category::where('is_active', true)
            ->where('service', $key)
            ->pluck('id')
            ->all();

        if (! $categoryIds) {
            return collect();
        }

        return Product::whereIn('category_id', $categoryIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => PosProductImage::photoUrl($product->image),
                    'icon' => self::categoryIcon($product->name),
                    'price' => (int) $product->price,
                ];
            })
            ->values();
    }

    public static function menuGroups()
    {
        // Parent categories only: hero uses the category main image; items include children.
        $preferred = [
            'coffee & tea' => 10,
            'iced & specialty' => 20,
            'tea & hot beverages' => 30,
            'fresh & detox juices' => 40,
            'smoothies' => 50,
            'food' => 60,
            'bar' => 70,
        ];

        $groups = collect();
        if (! self::enabled()) {
            return $groups;
        }

        $categories = Category::where('is_active', true)
            ->whereIn('service', ['coffee', 'restaurant'])
            ->orderBy('name')
            ->get();
        if ($categories->isEmpty()) {
            return $groups;
        }

        $byId = $categories->keyBy('id');
        $childrenMap = [];
        foreach ($categories as $category) {
            $pid = self::parentId($category);
            if ($pid > 0 && $pid !== (int) $category->id && $byId->has($pid)) {
                $childrenMap[$pid][] = (int) $category->id;
            }
        }

        $productsByCat = Product::whereIn('category_id', $categories->pluck('id')->all())
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy('category_id');

        $descendantIds = function ($rootId) use ($childrenMap) {
            $ids = [(int) $rootId];
            $queue = [(int) $rootId];
            while ($queue) {
                $parent = array_shift($queue);
                foreach ($childrenMap[$parent] ?? [] as $kid) {
                    $ids[] = (int) $kid;
                    $queue[] = (int) $kid;
                }
            }

            return array_values(array_unique($ids));
        };

        $mapItems = function ($categoryIds) use ($productsByCat) {
            $out = [];
            foreach ($categoryIds as $cid) {
                foreach ($productsByCat->get($cid, []) as $product) {
                    $out[] = self::menuItemPayload([
                        'id' => $product->id,
                        'name' => $product->name,
                        'details' => trim(strip_tags((string) $product->product_details)),
                        'image' => PosProductImage::photoUrl($product->image),
                        'icon' => self::categoryIcon($product->name),
                        'price' => (int) $product->price,
                    ]);
                }
            }

            return $out;
        };

        $roots = $categories->filter(function ($category) use ($byId) {
            $pid = self::parentId($category);
            if ($pid === 0 || $pid === (int) $category->id) {
                return true;
            }

            return ! $byId->has($pid);
        });

        foreach ($roots as $root) {
            $branchIds = $descendantIds($root->id);
            $items = $mapItems($branchIds);
            if (! $items) {
                continue;
            }

            $image = self::categoryImage($root) ?: self::menuFallbackImage($root->name);
            $childIds = $childrenMap[$root->id] ?? [];
            $buckets = [];
            if ($childIds) {
                $ownItems = $mapItems([$root->id]);
                if ($ownItems) {
                    $buckets[] = [
                        'id' => $root->id,
                        'title' => $root->name,
                        'icon' => self::categoryIcon($root->name),
                        'image' => $image,
                        'items' => $ownItems,
                    ];
                }
                foreach ($childIds as $childId) {
                    $child = $byId->get($childId);
                    if (! $child) {
                        continue;
                    }
                    $childItems = $mapItems($descendantIds($childId));
                    if (! $childItems) {
                        continue;
                    }
                    $buckets[] = [
                        'id' => $child->id,
                        'title' => $child->name,
                        'icon' => self::categoryIcon($child->name),
                        'image' => self::categoryImage($child) ?: null,
                        'items' => $childItems,
                    ];
                }
            }
            if (! $buckets) {
                $buckets = [[
                    'id' => $root->id,
                    'title' => $root->name,
                    'icon' => self::categoryIcon($root->name),
                    'image' => $image,
                    'items' => $items,
                ]];
            }

            $groups->push([
                'id' => $root->id,
                'name' => $root->name,
                'icon' => self::categoryIcon($root->name),
                'tagline' => '',
                'blurb' => '',
                'image' => $image,
                'items' => $items,
                'buckets' => $buckets,
                'has_children' => ! empty($childIds),
            ]);
        }

        return $groups->sortBy(function ($group) use ($preferred) {
            $key = strtolower(trim((string) $group['name']));

            return $preferred[$key] ?? (1000 + (int) $group['id']);
        })->values();
    }

    protected static function menuItemPayload(array $item)
    {
        $flavors = [];
        if (stripos($item['name'], 'Flavored Tea') !== false) {
            $flavors = ['Cinnamon', 'Earl Grey', 'Chamomile', 'Masala', 'Clover', 'Peppermint', 'Chai', 'Hibiscus'];
        }

        return [
            'id' => $item['id'],
            'name' => $item['name'],
            'details' => $item['details'],
            'image' => $item['image'] ?: '',
            'icon' => $item['icon'] ?? 'coffee',
            'price' => $item['price'],
            'flavors' => $flavors,
        ];
    }

    protected static function menuFallbackImage($name)
    {
        $map = [
            'Coffee & Tea' => 'branding/menu/coffee-tea.jpg',
            'Iced & Specialty' => 'branding/menu/iced-specialty.jpg',
            'Tea & Hot Beverages' => 'branding/menu/tea-hot.jpg',
            'Fresh & Detox Juices' => 'branding/menu/juices.jpg',
            'Smoothies' => 'branding/menu/smoothies.jpg',
            'Food' => 'branding/menu/food.jpg',
        ];
        $file = $map[$name] ?? 'branding/menu/coffee-tea.jpg';
        if (is_file(public_path($file))) {
            return asset('public/'.$file).'?v=3';
        }

        return url('public/branding/menu/coffee-tea.jpg').'?v=3';
    }

    protected static function directProductCounts(array $categoryIds)
    {
        if (! $categoryIds) {
            return [];
        }

        return Product::whereIn('category_id', $categoryIds)
            ->where('is_active', true)
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id')
            ->all();
    }

    protected static function descendantIds($categoryId)
    {
        static $childrenMap = null;
        if ($childrenMap === null) {
            $childrenMap = [];
            if (self::enabled()) {
                foreach (Category::where('is_active', true)->get(['id', 'parent_id']) as $row) {
                    $pid = (int) ($row->parent_id ?: 0);
                    if ($pid > 0 && $pid !== (int) $row->id) {
                        $childrenMap[$pid][] = (int) $row->id;
                    }
                }
            }
        }

        $ids = [];
        $queue = [(int) $categoryId];
        while ($queue) {
            $parent = array_shift($queue);
            foreach ($childrenMap[$parent] ?? [] as $kid) {
                $ids[] = (int) $kid;
                $queue[] = (int) $kid;
            }
        }

        return $ids;
    }
}
