<?php

use App\BeyondProfile;
use App\BeyondUser;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class LocalAdminSeeder extends Seeder
{
    public function run()
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles')) {
            return;
        }

        $phone = '+237675321739';
        $email = 'admin@welcome2kigali.local';
        $username = 'admin';
        $password = 'system';

        $roleId = DB::table('roles')->where('name', 'Admin')->value('id');
        if (! $roleId) {
            $payload = [
                'name' => 'Admin',
                'description' => 'Full access',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('roles', 'guard_name')) {
                $payload['guard_name'] = 'web';
            }
            $roleId = DB::table('roles')->insertGetId($payload);
        }

        try {
            $spatieRole = SpatieRole::find($roleId);
            if ($spatieRole && Schema::hasTable('permissions')) {
                $this->ensureAdminPermissions($spatieRole);
            }
        } catch (\Throwable $e) {
            // Role table may not be fully Spatie-compatible on a fresh seed.
        }

        $user = User::whereRaw('LOWER(name) = ?', [$username])
            ->orWhereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        $payload = [
            'name' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'phone' => $phone,
            'role_id' => $roleId,
            'is_active' => 1,
            'is_deleted' => 0,
            'otp_verify' => 0,
            'must_set_password' => 0,
        ];
        if (Schema::hasColumn('users', 'username')) {
            $payload['username'] = $username;
        }

        if ($user) {
            $user->fill($payload)->save();
        } else {
            $user = User::create($payload);
        }

        if (Schema::hasTable('be_users')) {
            $beyond = BeyondUser::whereRaw('LOWER(username) = ?', [$username])
                ->orWhereRaw('LOWER(email) = ?', [strtolower($email)])
                ->first();
            $beyondPayload = [
                'name' => 'Administrator',
                'email' => $email,
                'username' => $username,
                'password_hash' => Hash::make($password),
                'role' => 'super_admin',
                'status' => 'active',
                'phone' => $phone,
                'must_change_credentials' => false,
            ];
            if ($beyond) {
                $beyond->fill($beyondPayload)->save();
            } else {
                $beyondPayload['id'] = (string) Str::uuid();
                $beyond = BeyondUser::create($beyondPayload);
            }
            if (Schema::hasTable('be_profiles')) {
                BeyondProfile::updateOrCreate(
                    ['id' => $beyond->id],
                    [
                        'email' => $email,
                        'full_name' => 'Administrator',
                        'phone' => $phone,
                        'role' => 'super_admin',
                        'username' => $username,
                        'status' => 'active',
                    ]
                );
            }
        }

        if (Schema::hasTable('general_settings')) {
            $defaults = [
                'site_title' => 'Welcome 2 Kigali Expats Club',
                'staff_access' => 'all',
                'currency' => 1,
                'currency_position' => 'prefix',
                'date_format' => 'd/m/Y',
                'theme' => 'default.css',
                'developed_by' => 'Sr. Engr. Tefu R. Mbole',
                'app_version' => class_exists(\App\Support\AppVersion::class) ? \App\Support\AppVersion::erp() : 'W2K_V_1.1.9',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $defaults = array_filter($defaults, function ($key) {
                return Schema::hasColumn('general_settings', $key);
            }, ARRAY_FILTER_USE_KEY);

            if (DB::table('general_settings')->count() === 0) {
                DB::table('general_settings')->insert($defaults);
            } else {
                $current = DB::table('general_settings')->latest()->first();
                $patch = [];
                if (isset($defaults['theme']) && (! $current->theme || ! preg_match('/\.css$/', $current->theme))) {
                    $patch['theme'] = 'default.css';
                }
                if (isset($defaults['date_format']) && empty($current->date_format)) {
                    $patch['date_format'] = 'd/m/Y';
                }
                if (isset($defaults['staff_access']) && empty($current->staff_access)) {
                    $patch['staff_access'] = 'all';
                }
                if ($patch) {
                    $patch['updated_at'] = now();
                    DB::table('general_settings')->where('id', $current->id)->update($patch);
                }
            }
        }

        if ($this->command) {
            $this->command->info("Admin ready: {$username} / {$password} / {$phone}");
        }
    }

    private function ensureAdminPermissions(SpatieRole $spatieRole)
    {
        $names = [
            'dashboard',
            'search_all_products',
            'one_time_otp',
            'category',
        ];
        $sources = [
            resource_path('views/layout/main.blade.php'),
            resource_path('views/index.blade.php'),
            app_path('Http/Controllers/RoleController.php'),
        ];
        foreach ($sources as $path) {
            if (! is_file($path)) {
                continue;
            }
            $src = file_get_contents($path);
            if (preg_match_all("/where\\('name',\\s*'([^']+)'\\)/", $src, $m)) {
                $names = array_merge($names, $m[1]);
            }
            if (preg_match_all("/firstOrCreate\\(\\['name' => '([^']+)'\\]\\)/", $src, $m)) {
                $names = array_merge($names, $m[1]);
            }
            if (preg_match_all("/findOrCreate\\('([^']+)'/", $src, $m)) {
                $names = array_merge($names, $m[1]);
            }
        }

        foreach (array_unique($names) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $spatieRole->syncPermissions(Permission::all());
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
