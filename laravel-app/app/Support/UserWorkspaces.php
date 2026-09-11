<?php

namespace App\Support;

use App\BeyondProfile;
use App\BeyondUser;
use App\Customer;
use App\InternshipEnrolment;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * One person, one login: Member / Admin / Student workspaces.
 */
class UserWorkspaces
{
    const SESSION_KEY = 'active_workspace';

    const MEMBER = 'member';
    const ADMIN = 'admin';
    const STUDENT = 'student';

    /**
     * @return array member|admin|student => bool
     */
    public static function available($user, $beyondUser = null)
    {
        return [
            self::MEMBER => self::hasMember($user, $beyondUser),
            self::ADMIN => self::hasAdmin($user, $beyondUser),
            self::STUDENT => self::hasStudent($user, $beyondUser),
        ];
    }

    /**
     * @return string[]
     */
    public static function keys($user, $beyondUser = null)
    {
        $out = [];
        foreach (self::available($user, $beyondUser) as $key => $yes) {
            if ($yes) {
                $out[] = $key;
            }
        }

        return $out;
    }

    public static function hasMember($user, $beyondUser = null)
    {
        if ($user) {
            if ((int) $user->role_id === 5) {
                return true;
            }
            if (self::table('customers') && Customer::where('user_id', $user->id)->exists()) {
                return true;
            }
            if (self::hasMembershipForUser($user)) {
                return true;
            }
        }
        if ($beyondUser && in_array(strtolower((string) $beyondUser->role), ['customer', 'member'], true)) {
            return true;
        }

        return false;
    }

    public static function hasAdmin($user, $beyondUser = null)
    {
        if ($user) {
            if (StaffAccess::canManageSite($user)) {
                return true;
            }
            $name = self::roleName($user);
            if (! in_array($name, ['customer', 'intern', ''], true) && (int) $user->role_id !== 5) {
                return true;
            }
        }
        if ($beyondUser && in_array(strtolower((string) $beyondUser->role), ['admin', 'super_admin', 'director', 'manager'], true)) {
            return true;
        }

        return false;
    }

    public static function hasStudent($user, $beyondUser = null)
    {
        if ($user) {
            if (self::roleName($user) === 'intern') {
                return true;
            }
            if (self::table('internship_enrolments')
                && InternshipEnrolment::where('student_user_id', $user->id)->exists()) {
                return true;
            }
            $match = self::findBeyondUser($user);
            if ($match && strtolower((string) $match->role) === 'student') {
                return true;
            }
        }
        if ($beyondUser && strtolower((string) $beyondUser->role) === 'student') {
            return true;
        }

        return false;
    }

    public static function current($user = null)
    {
        $user = $user ?: Auth::guard('web')->user();
        $beyond = Auth::guard('beyond')->user();
        $keys = self::keys($user, $beyond);
        $session = session(self::SESSION_KEY);
        if ($session && in_array($session, $keys, true)) {
            return $session;
        }
        if (count($keys) === 1) {
            return $keys[0];
        }

        return null;
    }

    public static function set($workspace)
    {
        session([self::SESSION_KEY => $workspace]);
    }

    public static function is($workspace, $user = null)
    {
        return self::current($user) === $workspace;
    }

    public static function afterLoginRedirect($user, $beyondUser = null)
    {
        $keys = self::keys($user, $beyondUser);
        if (count($keys) === 1) {
            self::set($keys[0]);

            return self::urlFor($user, $keys[0], $beyondUser);
        }
        if (count($keys) === 0) {
            self::set(self::ADMIN);

            return '/admin';
        }
        $session = session(self::SESSION_KEY);
        if ($session && in_array($session, $keys, true)) {
            return self::urlFor($user, $session, $beyondUser);
        }

        return route('workspace.choose');
    }

    public static function urlFor($user, $workspace, $beyondUser = null)
    {
        if ($workspace === self::MEMBER) {
            return '/admin';
        }
        if ($workspace === self::ADMIN) {
            if ($user) {
                $supervisor = InternCompliance::supervisorPostLoginRedirect($user);
                if ($supervisor) {
                    return $supervisor;
                }
            }

            return '/admin';
        }
        if ($workspace === self::STUDENT) {
            if ($user && self::internDashboardAvailable($user)) {
                try {
                    return route('internship.student.dashboard');
                } catch (\Throwable $e) {
                }
            }

            return '/student/dashboard';
        }

        return '/admin';
    }

    public static function labels()
    {
        return [
            self::MEMBER => [
                'title' => __('site.workspace.member'),
                'desc' => __('site.workspace.member_desc'),
            ],
            self::ADMIN => [
                'title' => __('site.workspace.admin'),
                'desc' => __('site.workspace.admin_desc'),
            ],
            self::STUDENT => [
                'title' => __('site.workspace.student'),
                'desc' => __('site.workspace.student_desc'),
            ],
        ];
    }

    /**
     * Active ERP users matching email, last-9 phone, or exact name.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function findMatchingUsers($identifier)
    {
        $id = trim((string) $identifier);
        if ($id === '') {
            return collect();
        }

        $query = User::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->where('is_deleted', 0)
                    ->orWhere('is_deleted', false)
                    ->orWhereNull('is_deleted');
            });

        $found = collect();
        if (filter_var($id, FILTER_VALIDATE_EMAIL)) {
            $found = (clone $query)->whereRaw('LOWER(email) = ?', [strtolower($id)])->get();
            if ($found->isNotEmpty()) {
                return $found;
            }
        }

        $digits = preg_replace('/\D+/', '', $id);
        if (strlen($digits) >= 8) {
            $found = self::applyPhoneMatch(clone $query, $id)->get();
            if ($found->isNotEmpty()) {
                return $found;
            }
        }

        return (clone $query)->whereRaw('LOWER(name) = ?', [strtolower($id)])->get();
    }

    /**
     * Among users whose password matches, prefer Admin/staff over customer.
     *
     * @param  \Illuminate\Support\Collection|array  $users
     * @return \App\User|null
     */
    public static function pickUserForPassword($users, $password)
    {
        $matches = collect($users)->filter(function ($user) use ($password) {
            return $user && ! empty($user->password) && Hash::check($password, $user->password);
        })->values();

        if ($matches->isEmpty()) {
            return null;
        }

        return $matches->sortByDesc(function ($user) {
            return self::staffRank($user);
        })->first();
    }

    public static function preferStaff($users)
    {
        $users = collect($users);
        if ($users->isEmpty()) {
            return null;
        }

        return $users->sortByDesc(function ($user) {
            return self::staffRank($user);
        })->first();
    }

    public static function findExistingByPhoneOrEmail($phone = null, $email = null)
    {
        $query = User::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->where('is_deleted', 0)
                    ->orWhere('is_deleted', false)
                    ->orWhereNull('is_deleted');
            });

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $byEmail = (clone $query)->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->get();
            if ($byEmail->isNotEmpty()) {
                return self::preferStaff($byEmail);
            }
        }

        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) >= 8) {
            $byPhone = self::applyPhoneMatch(clone $query, $phone)->get();
            if ($byPhone->isNotEmpty()) {
                return self::preferStaff($byPhone);
            }
        }

        return null;
    }

    public static function applyPhoneMatch($query, $phone)
    {
        $raw = trim((string) $phone);
        $digits = preg_replace('/\D+/', '', $raw);
        $tail = strlen($digits) >= 8 ? substr($digits, -9) : $digits;

        return $query->where(function ($q) use ($raw, $digits, $tail) {
            $q->where('phone', $raw)
                ->orWhere('phone', $digits)
                ->orWhere('phone', '+'.$digits);
            if ($tail !== '') {
                $q->orWhereRaw(
                    "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,''), '+', ''), ' ', ''), '-', ''), '(', ''), 9) = ?",
                    [$tail]
                );
            }
        });
    }

    public static function staffRank($user)
    {
        if (! $user) {
            return -1;
        }
        if (StaffAccess::canManageSite($user)) {
            return 40;
        }
        $name = self::roleName($user);
        if ($name === 'intern') {
            return 10;
        }
        if ((int) $user->role_id === 5 || $name === 'customer') {
            return 0;
        }

        return 20;
    }

    /**
     * Log the matching Beyond portal user into the beyond guard.
     */
    public static function bridgeBeyond($erpUser)
    {
        if (! $erpUser || ! self::table('be_users')) {
            return null;
        }
        $beyond = self::findBeyondUser($erpUser);
        if (! $beyond) {
            return null;
        }
        try {
            Auth::guard('beyond')->login($beyond, false);
        } catch (\Throwable $e) {
            \Log::warning('bridgeBeyond login skipped: '.$e->getMessage());

            return null;
        }
        session(['beyond_otp_verified' => true]);

        return $beyond;
    }

    /**
     * Log a matching ERP user into the web guard (any role, not only admin).
     */
    public static function bridgeErp($beyondUser)
    {
        if (! $beyondUser) {
            return null;
        }
        $email = trim((string) $beyondUser->email);
        $phone = $beyondUser->phone;
        if (! $phone) {
            $profile = BeyondProfile::find($beyondUser->id);
            $phone = $profile ? $profile->phone : null;
        }
        $erp = self::findExistingByPhoneOrEmail($phone, $email);
        if (! $erp) {
            return null;
        }
        $erp->otp_verify = 1;
        $erp->save();
        Auth::guard('web')->login($erp, false);

        return $erp;
    }

    public static function findBeyondUser($erpUser)
    {
        if (! $erpUser || ! self::table('be_users')) {
            return null;
        }
        $email = trim((string) $erpUser->email);
        if ($email !== '') {
            $found = BeyondUser::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if ($found) {
                return $found;
            }
        }
        $digits = preg_replace('/\D+/', '', (string) $erpUser->phone);
        if (strlen($digits) < 8) {
            return null;
        }
        $tail = substr($digits, -9);

        return BeyondUser::where(function ($q) use ($erpUser, $digits, $tail) {
            $q->where('phone', $erpUser->phone)
                ->orWhere('phone', $digits)
                ->orWhere('phone', '+'.$digits)
                ->orWhereRaw(
                    "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,''), '+', ''), ' ', ''), '-', ''), '(', ''), 9) = ?",
                    [$tail]
                );
        })->first();
    }

    /**
     * Create or reuse a POS customer row for this user (Member workspace).
     */
    public static function ensureCustomer($user, array $extra = [])
    {
        if (! $user || ! self::table('customers')) {
            return null;
        }
        $existing = Customer::where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        $digits = preg_replace('/\D+/', '', (string) ($extra['phone'] ?? $user->phone));
        if (strlen($digits) >= 8) {
            $tail = substr($digits, -9);
            $byPhone = Customer::whereRaw(
                "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone_number,''), '+', ''), ' ', ''), '-', ''), '(', ''), 9) = ?",
                [$tail]
            )->first();
            if ($byPhone) {
                if (empty($byPhone->user_id)) {
                    $byPhone->user_id = $user->id;
                    $byPhone->save();
                }

                return $byPhone;
            }
        }

        $groupId = DB::table('customer_groups')->where('is_active', 1)->value('id')
            ?: DB::table('customer_groups')->value('id')
            ?: 1;

        return Customer::create([
            'customer_group_id' => $extra['customer_group_id'] ?? $groupId,
            'user_id' => $user->id,
            'name' => $extra['name'] ?? $user->name,
            'company_name' => $extra['company_name'] ?? $user->company_name,
            'email' => $extra['email'] ?? $user->email,
            'phone_number' => $extra['phone_number'] ?? $extra['phone'] ?? $user->phone,
            'address' => $extra['address'] ?? 'Kigali',
            'city' => $extra['city'] ?? 'Kigali',
            'state' => $extra['state'] ?? null,
            'is_active' => 1,
            'points' => 0,
        ]);
    }

    public static function internDashboardAvailable($user)
    {
        if (! $user) {
            return false;
        }
        if (self::roleName($user) === 'intern') {
            return true;
        }
        if (! self::table('internship_enrolments')) {
            return false;
        }

        return InternshipEnrolment::where('student_user_id', $user->id)->exists();
    }

    protected static function hasMembershipForUser($user)
    {
        if (! $user || ! self::table('memberships') || ! self::table('customers')) {
            return false;
        }
        $customerIds = Customer::where('user_id', $user->id)->pluck('id');
        if ($customerIds->isEmpty()) {
            return false;
        }

        return DB::table('memberships')->whereIn('customer_id', $customerIds)->exists();
    }

    protected static function roleName($user)
    {
        if (! $user || empty($user->role_id)) {
            return '';
        }

        return strtolower(trim((string) DB::table('roles')->where('id', $user->role_id)->value('name')));
    }

    protected static function table($name)
    {
        try {
            return Schema::hasTable($name);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
