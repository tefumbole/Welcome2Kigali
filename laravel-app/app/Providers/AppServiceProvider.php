<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use DB;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function boot()
    {
        /*if( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
            URL::forceScheme('https');
        }*/
        $locale = 'en';
        if (! empty($_COOKIE['language']) && in_array($_COOKIE['language'], ['en', 'fr'], true)) {
            $locale = $_COOKIE['language'];
        }
        \App::setLocale($locale);
        if (class_exists(\Carbon\Carbon::class)) {
            \Carbon\Carbon::setLocale($locale);
        }
        Schema::defaultStringLength(191);

        // Guard against boot before the database is installed/migrated (fresh install, CLI, migrations).
        if (! $this->settingsAvailable()) {
            View::share('general_setting', null);
            View::share('currency', $this->fallbackCurrency());
            View::share('alert_product', 0);
            return;
        }

        //get general setting value
        $general_setting = DB::table('general_settings')->latest()->first();
        // Live version from laravel-app/VERSION (updated on each commit/push)
        if ($general_setting) {
            $general_setting->app_version = \App\Support\AppVersion::erp();
        }
        $currency = $this->resolveCurrency($general_setting);
        View::share('general_setting', $general_setting);
        View::share('currency', $currency);
        if ($general_setting) {
            config([
                'staff_access' => $general_setting->staff_access,
                'date_format' => $general_setting->date_format,
                'currency' => $currency ? $currency->code : null,
                'currency_position' => $general_setting->currency_position,
            ]);
        }

        $alert_product = DB::table('products')->where('is_active', true)->whereColumn('alert_quantity', '>', 'qty')->count();
        View::share('alert_product', $alert_product);

        View::composer('frontend.layout.main', function ($view) {
            $categories = Cache::remember('frontend_nav_categories', 3600, function () {
                return \App\Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            });
            $view->with('categories', $categories);
        });
    }

    /**
     * Whether the core settings table exists and can be queried.
     * Prevents boot-time crashes on a fresh (unmigrated) database.
     */
    private function settingsAvailable()
    {
        try {
            return Schema::hasTable('general_settings');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Always return a currency object so Blade `$currency->code` never 500s.
     */
    private function resolveCurrency($general_setting)
    {
        try {
            if (! Schema::hasTable('currencies')) {
                return $this->fallbackCurrency();
            }
            $id = $general_setting && ! empty($general_setting->currency) ? $general_setting->currency : null;
            if ($id) {
                $found = \App\Currency::find($id);
                if ($found) {
                    return $found;
                }
            }
            $found = \App\Currency::where('code', 'RWF')->first() ?: \App\Currency::orderBy('id')->first();

            return $found ?: $this->fallbackCurrency();
        } catch (\Throwable $e) {
            return $this->fallbackCurrency();
        }
    }

    private function fallbackCurrency()
    {
        $currency = new \App\Currency();
        $currency->id = 0;
        $currency->name = 'Rwandan Franc';
        $currency->code = 'RWF';
        $currency->exchange_rate = 1;

        return $currency;
    }
}
