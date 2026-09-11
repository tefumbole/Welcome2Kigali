<?php

namespace App\Support;

use App\SiteSetting;

/**
 * Canonical definitions and saved ordering for the public landing menu and the
 * admin side menu. Used by the Site Content admin screen and both layouts.
 */
class SiteMenu
{
    /** Public site header items: key => label (default order). */
    public static function landingItems()
    {
        return [
            'home'     => 'Home',
            'about'    => 'About',
            'events'   => 'Events',
            'menu'     => 'Menu',
            'register' => 'Become a Member',
            // Login is rendered separately in the header
        ];
    }

    /** Admin sidebar top-level items: key => label (default order). Keys match
     *  the sidebar collapse targets (#product, #purchase, ...). */
    public static function sideItems()
    {
        return [
            'dashboard'    => 'Dashboard',
            'help'         => 'Help',
            'site-content' => 'Site Content',
            'leaders'      => 'About Us Leaders',
            'invitations'  => 'Digital Invitations',
            'internships'  => 'Internships',
            'product'      => 'Product',
            'purchase'     => 'Purchase',
            'sale'         => 'Sale',
            'booking'      => 'Rental Module',
            'events'       => 'Events',
            'tasks'        => 'Task Manager',
            'jobs'         => 'Job Board',
            'contracts'    => 'Contracts',
            'permissions'  => 'Permissions',
            'announcements'=> 'Announcements',
            'courses'      => 'Courses',
            'membership'   => 'Membership',
            'timesheets'   => 'TimeSheets (Employee)',
            'timesheet-admin' => 'TimeSheet Admin',
            'shop'         => 'Shops',
            'order'        => 'Online Order',
            'payments'     => 'Payments',
            'letter'       => 'Letters',
            'expense'      => 'Expense',
            'quotation'    => 'Quotation',
            'assets'       => 'Fixed Assets',
            'transfer'     => 'Transfer',
            'return'       => 'Return',
            'account'      => 'Accounting',
            'hrm'          => 'HRM',
            'people'       => 'People',
            'report'       => 'Reports',
            'setting'      => 'Settings',
        ];
    }

    /**
     * Merge the saved order with the canonical items: saved keys first (only if
     * still valid), then any new/unsaved keys appended in their default order.
     */
    public static function ordered($settingKey, array $items)
    {
        $saved = SiteSetting::getValue($settingKey, []);
        if (! is_array($saved)) {
            $saved = [];
        }

        $ordered = [];
        foreach ($saved as $k) {
            if (isset($items[$k]) && ! in_array($k, $ordered, true)) {
                $ordered[] = $k;
            }
        }
        foreach (array_keys($items) as $k) {
            if (! in_array($k, $ordered, true)) {
                $ordered[] = $k;
            }
        }

        return $ordered;
    }

    public static function hiddenKeys($settingKey)
    {
        $hidden = SiteSetting::getValue($settingKey, []);

        return is_array($hidden) ? array_values($hidden) : [];
    }

    public static function isHidden($settingKey, $itemKey)
    {
        return in_array($itemKey, self::hiddenKeys($settingKey), true);
    }

    public static function itemLabels($settingKey, array $defaults)
    {
        $saved = SiteSetting::getValue($settingKey, []);
        if (! is_array($saved)) {
            $saved = [];
        }
        $out = [];
        foreach ($defaults as $k => $label) {
            $custom = isset($saved[$k]) ? trim((string) $saved[$k]) : '';
            $out[$k] = $custom !== '' ? $custom : $label;
        }

        return $out;
    }

    public static function landingOrder()
    {
        return self::ordered('landing_menu_order', self::landingItems());
    }

    public static function sideOrder()
    {
        return self::ordered('side_menu_order', self::sideItems());
    }

    /** Horizontal Settings hub tabs (system name, logo, units, …). */
    public static function settingsHubTabs()
    {
        $uid = auth()->id();

        return [
            ['label' => 'General Setting', 'url' => url('setting/general_setting'), 'match' => 'setting/general_setting'],
            ['label' => 'Role Permission', 'url' => url('role'), 'match' => 'role'],
            ['label' => 'Warehouse', 'url' => url('warehouse'), 'match' => 'warehouse'],
            ['label' => 'Customer Group', 'url' => url('customer_group'), 'match' => 'customer_group'],
            ['label' => 'Brand', 'url' => url('brand'), 'match' => 'brand'],
            ['label' => 'Unit', 'url' => url('unit'), 'match' => 'unit'],
            ['label' => 'Currency', 'url' => url('currency'), 'match' => 'currency'],
            ['label' => 'Tax', 'url' => url('tax'), 'match' => 'tax'],
            ['label' => 'User Profile', 'url' => $uid ? url('user/profile/'.$uid) : url('user'), 'match' => 'user/profile'],
            ['label' => 'Mail Setting', 'url' => url('setting/mail_setting'), 'match' => 'setting/mail_setting'],
            ['label' => 'Reward Point Setting', 'url' => url('setting/reward-point-setting'), 'match' => 'setting/reward-point-setting'],
            ['label' => 'POS Settings', 'url' => url('setting/pos_setting'), 'match' => 'setting/pos_setting'],
            ['label' => 'env Settings', 'url' => url('setting/env_setting'), 'match' => 'setting/env_setting'],
            ['label' => 'My Transactions', 'url' => url('my-transactions/'.date('Y').'/'.date('m')), 'match' => 'my-transactions'],
            ['label' => 'Activity Logs', 'url' => url('setting/activity-logs'), 'match' => 'setting/activity-logs'],
        ];
    }

    public static function isSettingsHubPath($path = null)
    {
        $path = trim($path !== null ? $path : request()->path(), '/');
        foreach (self::settingsHubTabs() as $tab) {
            $m = trim($tab['match'], '/');
            if ($path === $m || strpos($path, $m) === 0) {
                return true;
            }
        }

        return false;
    }

    /** Settings submenu items inside #setting (key => label). */
    public static function settingsItems()
    {
        return [
            'general-setting'    => 'General Setting',
            'brand'              => 'Brand',
            'unit'               => 'Unit',
            'category'           => 'Category',
            'currency'           => 'Currency',
            'tax'                => 'Tax',
            'warehouse'          => 'Warehouse',
            'customer-group'     => 'Customer Group',
            'role'               => 'Role Permission',
            'notification'       => 'Send Notification',
            'user'               => 'User Profile',
            'my-transactions'    => 'My Transactions',
            'backup-database'    => 'Backup Database',
            'empty-database'     => 'Empty Database',
            'activity-logs'      => 'Activity Logs',
            'env-setting'        => '.env Settings',
            'mail-setting'       => 'Mail Setting',
            'reward-point-setting' => 'Reward Point Setting',
            'pos-setting'        => 'POS Settings',
        ];
    }

    public static function settingsOrder()
    {
        return self::ordered('settings_menu_order', self::settingsItems());
    }

    /** People submenu items inside #people (key => label). */
    public static function peopleItems()
    {
        return [
            'user-list'       => 'User List',
            'interns'         => 'Interns',
            'add-user'        => 'Add User',
            'customer-list'   => 'Customer List',
            'add-customer'    => 'Add Customer',
            'people-transfer' => 'Export / Import People',
            'biller-list'     => 'Biller List',
            'add-biller'      => 'Add Biller',
            'supplier-list'   => 'Supplier List',
            'add-supplier'    => 'Add Supplier',
        ];
    }

    public static function peopleOrder()
    {
        return self::ordered('people_menu_order', self::peopleItems());
    }

    /** Map people submenu <li id="..."> to stable reorder keys. */
    public static function peopleLiKeyMap()
    {
        return [
            'user-list-menu'       => 'user-list',
            'user-applicants-menu' => 'interns',
            'user-create-menu'     => 'add-user',
            'customer-list-menu'   => 'customer-list',
            'customer-create-menu' => 'add-customer',
            'people-transfer-menu' => 'people-transfer',
            'biller-list-menu'     => 'biller-list',
            'biller-create-menu'   => 'add-biller',
            'supplier-list-menu'   => 'supplier-list',
            'supplier-create-menu' => 'add-supplier',
        ];
    }

    /** Map settings submenu <li id="..."> to stable reorder keys. */
    public static function settingsLiKeyMap()
    {
        return [
            'role-menu'               => 'role',
            'notification-menu'         => 'notification',
            'warehouse-menu'          => 'warehouse',
            'customer-group-menu'     => 'customer-group',
            'brand-menu'              => 'brand',
            'unit-menu'               => 'unit',
            'settings-category-menu'  => 'category',
            'currency-menu'           => 'currency',
            'tax-menu'                => 'tax',
            'user-menu'               => 'user',
            'my-transactions-menu'    => 'my-transactions',
            'backup-database-menu'    => 'backup-database',
            'empty-database-menu'     => 'empty-database',
            'general-setting-menu'    => 'general-setting',
            'activity-logs-menu'      => 'activity-logs',
            'env-setting-menu'        => 'env-setting',
            'mail-setting-menu'       => 'mail-setting',
            'reward-point-setting-menu' => 'reward-point-setting',
            'pos-setting-menu'        => 'pos-setting',
        ];
    }
}
