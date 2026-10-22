<?php

namespace App\Support;

use App\Services\Restaurant\RestaurantAccess;
use App\Support\StaffAccess;
use Illuminate\Support\Facades\Auth;

/**
 * Staff Help topics: one topic per admin menu, gated by permissions.
 */
class HelpTopics
{
    /**
     * @return array<string, array{title:string,nav:string,permissions?:string[],any?:bool,gate?:string}>
     */
    public static function catalog()
    {
        return [
            'start' => ['title' => 'Sign in', 'nav' => 'Start', 'any' => true],
            'find-help' => ['title' => 'Find Help', 'nav' => 'Find Help', 'any' => true],
            'website' => ['title' => 'Public website', 'nav' => 'Website', 'permissions' => ['site-content', 'site_content', 'products-index']],
            'products' => ['title' => 'Products', 'nav' => 'Products', 'permissions' => ['products-index', 'products-add', 'products-edit']],
            'catalog' => ['title' => 'Live catalog', 'nav' => 'Catalog', 'permissions' => ['products-index']],
            'pos' => ['title' => 'POS & sales', 'nav' => 'POS', 'permissions' => ['sales-add', 'quotes', 'sale']],
            'content' => ['title' => 'Site Content', 'nav' => 'Site Content', 'permissions' => ['site-content', 'site_content']],
            'members' => ['title' => 'Membership', 'nav' => 'Membership', 'permissions' => ['membership', 'membership-index']],
            'laundry' => ['title' => 'Laundry', 'nav' => 'Laundry', 'gate' => 'laundry'],
            'restaurant' => ['title' => 'Orders · Restaurant', 'nav' => 'Orders', 'gate' => 'orders'],
            'restaurant-coffee' => ['title' => 'Orders · Coffee & Tea', 'nav' => 'Coffee', 'gate' => 'orders'],
            'restaurant-bar' => ['title' => 'Orders · Bar', 'nav' => 'Bar', 'gate' => 'orders'],
            'orders' => ['title' => 'Online orders', 'nav' => 'Online', 'gate' => 'orders'],
            'rental' => ['title' => 'Rental', 'nav' => 'Rental', 'permissions' => ['booking', 'bookings-index', 'rental']],
            'quotations' => ['title' => 'Quotations', 'nav' => 'Quotations', 'permissions' => ['quotes', 'quotations-index', 'quotation']],
            'contracts' => ['title' => 'Contracts', 'nav' => 'Contracts', 'permissions' => ['contracts', 'contracts-index']],
            'expenses' => ['title' => 'Expenses', 'nav' => 'Expenses', 'permissions' => ['expenses-index', 'expenses']],
            'returns' => ['title' => 'Returns', 'nav' => 'Returns', 'permissions' => ['returns-index', 'return']],
            'assets' => ['title' => 'Fixed assets', 'nav' => 'Assets', 'permissions' => ['assets', 'fixed-assets']],
            'events' => ['title' => 'Events', 'nav' => 'Events', 'permissions' => ['events', 'events-index']],
            'purchase' => ['title' => 'Purchases', 'nav' => 'Purchase', 'permissions' => ['purchases-index', 'purchases-add']],
            'payments' => ['title' => 'Awaiting Payment', 'nav' => 'Payments', 'permissions' => ['sales-index', 'payments']],
            'people' => ['title' => 'People', 'nav' => 'People', 'permissions' => ['customers-index', 'users-index', 'billers-index']],
            'accounting' => ['title' => 'Accounting', 'nav' => 'Accounting', 'permissions' => ['account-index', 'accounts-index']],
            'letters' => ['title' => 'Letters', 'nav' => 'Letters', 'permissions' => ['letters', 'letter']],
            'invitations' => ['title' => 'Digital invitations', 'nav' => 'Invitations', 'permissions' => ['invitations', 'online_invitation']],
            'internships' => ['title' => 'Internships', 'nav' => 'Internships', 'permissions' => ['internships', 'internship']],
            'tasks' => ['title' => 'Tasks & jobs', 'nav' => 'Tasks', 'permissions' => ['tasks', 'jobs', 'announcements', 'courses']],
            'reports' => ['title' => 'Reports', 'nav' => 'Reports', 'permissions' => ['reports']],
            'settings' => ['title' => 'Settings', 'nav' => 'Settings', 'permissions' => ['settings', 'general_setting']],
            'transfer' => ['title' => 'Stock transfer', 'nav' => 'Transfer', 'permissions' => ['transfers-index', 'transfer']],
            'whatsapp' => ['title' => 'WhatsApp links', 'nav' => 'WhatsApp', 'any' => true],
            'reviews' => ['title' => 'Guest reviews', 'nav' => 'Reviews', 'permissions' => ['site-content', 'site_content', 'products-index']],
            'services' => ['title' => 'Services catalog', 'nav' => 'Services', 'permissions' => ['site-content', 'site_content', 'products-index']],
        ];
    }

    /** Map sidebar submenu id → help topic key. */
    public static function menuMap()
    {
        return [
            'product' => 'products',
            'purchase' => 'purchase',
            'sale' => 'pos',
            'booking' => 'rental',
            'events-module' => 'events',
            'events' => 'events',
            'tasks-module' => 'tasks',
            'jobs-module' => 'tasks',
            'contracts-module' => 'contracts',
            'announcements-module' => 'tasks',
            'courses-module' => 'tasks',
            'membership' => 'members',
            'membership-module' => 'members',
            'laundry-module' => 'laundry',
            'laundry' => 'laundry',
            'timesheets-module' => 'tasks',
            'timesheet-admin-module' => 'tasks',
            'order' => 'restaurant',
            'letter' => 'letters',
            'expense' => 'expenses',
            'quotation' => 'quotations',
            'assets' => 'assets',
            'transfer' => 'transfer',
            'return' => 'returns',
            'account' => 'accounting',
            'people' => 'people',
            'report' => 'reports',
            'setting' => 'settings',
            'hrm' => 'people',
            'shop' => 'settings',
            'payments' => 'payments',
            'online_invitation' => 'invitations',
            'internship-module' => 'internships',
            'supervisor-module' => 'internships',
            'staff-permissions' => 'settings',
            'help-module' => 'start',
        ];
    }

    public static function canAccess($topicKey, array $permissions = null)
    {
        $catalog = self::catalog();
        if (! isset($catalog[$topicKey])) {
            return false;
        }
        $topic = $catalog[$topicKey];
        if (! empty($topic['any'])) {
            return true;
        }
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if (($topic['gate'] ?? '') === 'laundry') {
            return StaffAccess::canAnyLaundry();
        }
        if (($topic['gate'] ?? '') === 'orders') {
            return RestaurantAccess::canSeeOrders($user) && RestaurantAccess::installed();
        }
        $perms = $permissions;
        if ($perms === null) {
            $perms = self::userPermissionNames($user);
        }
        if (in_array('admin', $perms, true) || in_array('*', $perms, true)) {
            return true;
        }
        foreach ($topic['permissions'] ?? [] as $need) {
            if (in_array($need, $perms, true)) {
                return true;
            }
            // Loose match: permission contains the token
            foreach ($perms as $have) {
                if (stripos($have, $need) !== false) {
                    return true;
                }
            }
        }

        // If no permission list matched but user is staff with any module access, still allow common topics
        return empty($topic['permissions']);
    }

    public static function allowedTopics(array $permissions = null)
    {
        $out = [];
        foreach (self::catalog() as $key => $meta) {
            if (self::canAccess($key, $permissions)) {
                $out[$key] = $meta;
            }
        }

        return $out;
    }

    public static function userPermissionNames($user = null)
    {
        $user = $user ?: Auth::user();
        if (! $user) {
            return [];
        }
        $names = [];
        try {
            $role = \Spatie\Permission\Models\Role::find($user->role_id);
            if ($role) {
                foreach ($role->permissions as $permission) {
                    $names[] = $permission->name;
                }
            }
        } catch (\Throwable $e) {
        }

        return $names;
    }

    public static function normalizeTopic($topic)
    {
        $topic = trim((string) $topic);
        if ($topic === '' || $topic === 'guide') {
            return null;
        }
        $aliases = [
            'sale' => 'pos',
            'sales' => 'pos',
            'booking' => 'rental',
            'membership' => 'members',
            'member' => 'members',
            'site-content' => 'content',
            'coffee' => 'restaurant-coffee',
            'bar' => 'restaurant-bar',
            'online' => 'orders',
        ];
        if (isset($aliases[$topic])) {
            $topic = $aliases[$topic];
        }

        return isset(self::catalog()[$topic]) ? $topic : null;
    }
}
