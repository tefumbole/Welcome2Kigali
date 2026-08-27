<?php

namespace App\Http\Controllers;

use App\Membership;
use App\MembershipAgreement;
use App\MembershipApplication;
use App\MembershipAuditLog;
use App\MembershipBenefitRedemption;
use App\MembershipDocument;
use App\MembershipNotification;
use App\MembershipPayment;
use App\MembershipPlan;
use App\MembershipProductBenefit;
use App\MembershipPromotion;
use App\MembershipSetting;
use App\Product;
use App\Services\MembershipNotifier;
use App\Services\MembershipService;
use App\Support\MembershipQr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;

class MembershipAdminController extends Controller
{
    protected $memberships;
    protected $notifier;
    protected $all_permission = [];

    public function __construct(MembershipService $memberships, MembershipNotifier $notifier)
    {
        $this->memberships = $memberships;
        $this->notifier = $notifier;
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $role = Role::find(Auth::user()->role_id);
                if ($role) {
                    foreach (Role::findByName($role->name)->permissions as $permission) {
                        $this->all_permission[] = $permission->name;
                    }
                }
            }
            View::share('all_permission', $this->all_permission);

            return $next($request);
        });
    }

    protected function authorizeMembership($permission = 'memberships.view')
    {
        if ($this->hasMembershipAccess($permission)) {
            return;
        }
        abort(403, 'You are not allowed to access Membership.');
    }

    protected function authorizeSettings()
    {
        if ($this->isMembershipAdminRole() || $this->hasMembershipAccess('memberships.settings')) {
            return;
        }
        abort(403, 'Sales staff cannot change membership discount or settings.');
    }

    protected function isMembershipAdminRole()
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        $role = Role::find($user->role_id);

        return $role && in_array(strtolower($role->name), ['admin', 'owner', 'super admin'], true);
    }

    protected function hasMembershipAccess($permission)
    {
        if ($this->isMembershipAdminRole()) {
            return true;
        }
        if (in_array('membership_module', $this->all_permission, true)
            || in_array($permission, $this->all_permission, true)) {
            return true;
        }
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        $role = Role::find($user->role_id);
        if (! $role) {
            return false;
        }
        try {
            foreach (Role::findByName($role->name)->permissions as $p) {
                if ($p->name === 'membership_module' || $p->name === $permission) {
                    return true;
                }
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    protected function boolInput(Request $request, $key)
    {
        $value = $request->input($key);

        return $value === true || $value === 1 || $value === '1' || $value === 'on' || $value === 'yes';
    }

    public function dashboard()
    {
        $this->authorizeMembership();
        $stats = $this->memberships->dashboardStats();
        $recent = MembershipApplication::orderByDesc('id')->take(8)->get();
        $expiring = Membership::whereIn('status', ['EXPIRING', 'ACTIVE'])
            ->whereNotNull('expires_at')
            ->orderBy('expires_at')
            ->take(8)
            ->get();

        return view('membership.dashboard', compact('stats', 'recent', 'expiring'));
    }

    public function applications(Request $request)
    {
        $this->authorizeMembership();
        $q = MembershipApplication::with(['plan', 'customer'])->orderByDesc('id');
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $term = '%'.$request->q.'%';
            $q->where(function ($inner) use ($term) {
                $inner->where('full_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('reference', 'like', $term);
            });
        }
        $items = $q->paginate(30);

        return view('membership.applications', compact('items'));
    }

    public function showApplication($id)
    {
        $this->authorizeMembership();
        $application = MembershipApplication::with(['plan', 'agreement', 'documents', 'membership.customer', 'promotion'])->findOrFail($id);

        return view('membership.application_show', compact('application'));
    }

    public function approve($id)
    {
        $this->authorizeMembership('memberships.approve');
        $application = MembershipApplication::findOrFail($id);
        $membership = $this->memberships->approve($application, Auth::id());
        $payUrl = null;
        if ($membership->status === 'APPROVED_PENDING_PAYMENT' && $application->plan_id) {
            $plan = MembershipPlan::find($application->plan_id);
            if ($plan) {
                $payment = $this->memberships->createPendingPayment($membership, $plan, false);
                $payUrl = MembershipQr::renewUrl($membership).'?pay='.$payment->id;
            }
        }
        try {
            $this->notifier->approved($membership, $payUrl);
        } catch (\Throwable $e) {
        }

        return redirect()->route('membership.admin.applications.show', $id)->with('message', 'Application approved. Membership '.$membership->number.'.');
    }

    public function reject(Request $request, $id)
    {
        $this->authorizeMembership('memberships.reject');
        $application = MembershipApplication::findOrFail($id);
        $this->memberships->reject($application, $request->input('admin_note'), Auth::id());
        try {
            $this->notifier->rejected($application);
        } catch (\Throwable $e) {
        }

        return back()->with('message', 'Application rejected.');
    }

    public function moreInfo(Request $request, $id)
    {
        $this->authorizeMembership('memberships.approve');
        $application = MembershipApplication::findOrFail($id);
        $this->memberships->requestMoreInfo($application, $request->input('admin_note'), Auth::id());
        try {
            $this->notifier->moreInfo($application);
        } catch (\Throwable $e) {
        }

        return back()->with('message', 'Applicant was asked for more information.');
    }

    public function members(Request $request)
    {
        $this->authorizeMembership();
        $q = Membership::with(['customer', 'plan'])->orderByDesc('id');
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $term = '%'.$request->q.'%';
            $q->where(function ($inner) use ($term) {
                $inner->where('number', 'like', $term)
                    ->orWhereHas('customer', function ($c) use ($term) {
                        $c->where('name', 'like', $term)->orWhere('phone_number', 'like', $term)->orWhere('email', 'like', $term);
                    });
            });
        }
        $items = $q->paginate(30);

        return view('membership.members', compact('items'));
    }

    public function showMember($id)
    {
        $this->authorizeMembership();
        $membership = Membership::with(['customer', 'plan', 'payments', 'redemptions', 'application.documents'])->findOrFail($id);
        $plans = MembershipPlan::active()->get();

        return view('membership.member_show', compact('membership', 'plans'));
    }

    public function suspend(Request $request, $id)
    {
        $this->authorizeMembership('memberships.suspend');
        $this->memberships->suspend(Membership::findOrFail($id), $request->input('note'));

        return back()->with('message', 'Membership suspended.');
    }

    public function unsuspend($id)
    {
        $this->authorizeMembership('memberships.suspend');
        $this->memberships->unsuspend(Membership::findOrFail($id));

        return back()->with('message', 'Membership restored.');
    }

    public function cancel($id)
    {
        $this->authorizeMembership('memberships.suspend');
        $this->memberships->cancel(Membership::findOrFail($id));

        return back()->with('message', 'Membership cancelled.');
    }

    public function plans()
    {
        $this->authorizeMembership();
        $items = MembershipPlan::orderBy('sort_order')->get();

        return view('membership.plans', compact('items'));
    }

    public function storePlan(Request $request)
    {
        $this->authorizeSettings();
        $data = $request->validate([
            'code' => 'required|string|max:40',
            'name' => 'required|string|max:191',
            'duration_months' => 'required|integer|min:1',
            'fee' => 'required|numeric|min:0',
        ]);
        $data['is_active'] = $this->boolInput($request, 'is_active');
        $data['sort_order'] = (int) $request->input('sort_order', 0);
        MembershipPlan::create($data);
        $this->memberships->audit(null, null, 'plan.created', $data);

        return back()->with('message', 'Plan saved.');
    }

    public function updatePlan(Request $request, $id)
    {
        $this->authorizeSettings();
        $plan = MembershipPlan::findOrFail($id);
        $plan->name = $request->input('name', $plan->name);
        $plan->duration_months = (int) $request->input('duration_months', $plan->duration_months);
        $plan->fee = $request->input('fee', $plan->fee);
        $plan->is_active = $this->boolInput($request, 'is_active');
        $plan->save();
        $this->memberships->audit(null, null, 'plan.updated', ['id' => $plan->id, 'fee' => $plan->fee]);

        return back()->with('message', 'Plan updated.');
    }

    public function promotions()
    {
        $this->authorizeMembership('memberships.promotions');
        $items = MembershipPromotion::orderByDesc('id')->get();

        return view('membership.promotions', compact('items'));
    }

    public function storePromotion(Request $request)
    {
        $this->authorizeMembership('memberships.promotions');
        $this->authorizeSettings();
        MembershipPromotion::create([
            'name' => $request->input('name'),
            'type' => $request->input('type', 'free_days'),
            'free_days' => (int) $request->input('free_days', 90),
            'is_enabled' => $this->boolInput($request, 'is_enabled'),
            'starts_at' => $request->input('starts_at') ?: null,
            'ends_at' => $request->input('ends_at') ?: null,
            'description' => $request->input('description'),
        ]);

        return back()->with('message', 'Promotion saved.');
    }

    public function updatePromotion(Request $request, $id)
    {
        $this->authorizeMembership('memberships.promotions');
        $this->authorizeSettings();
        $row = MembershipPromotion::findOrFail($id);
        $row->name = $request->input('name', $row->name);
        $row->free_days = (int) $request->input('free_days', $row->free_days);
        $row->is_enabled = $this->boolInput($request, 'is_enabled');
        $row->starts_at = $request->input('starts_at') ?: null;
        $row->ends_at = $request->input('ends_at') ?: null;
        $row->description = $request->input('description', $row->description);
        $row->save();
        $this->memberships->audit(null, null, 'promotion.updated', ['id' => $row->id]);

        return back()->with('message', 'Promotion updated.');
    }

    public function benefits()
    {
        $this->authorizeMembership('memberships.benefits');
        $items = MembershipProductBenefit::with('product')->orderByDesc('id')->get();
        $products = Product::where('is_active', 1)->orderBy('name')->get(['id', 'name', 'code']);

        return view('membership.benefits', compact('items', 'products'));
    }

    public function storeBenefit(Request $request)
    {
        $this->authorizeMembership('memberships.benefits');
        $this->memberships->syncProductBenefit($request->input('product_id'), [
            'membership_benefit' => 1,
            'membership_benefit_kind' => $request->input('kind', 'free'),
            'membership_member_price' => $request->input('member_price'),
            'membership_benefit_qty' => $request->input('qty', 1),
            'membership_benefit_frequency' => $request->input('frequency', 'unlimited'),
        ]);

        return back()->with('message', 'Benefit saved.');
    }

    public function payments()
    {
        $this->authorizeMembership('memberships.payments');
        $items = MembershipPayment::with(['membership.customer', 'plan'])->orderByDesc('id')->paginate(40);

        return view('membership.payments', compact('items'));
    }

    public function agreements()
    {
        $this->authorizeMembership();
        $items = MembershipAgreement::orderByDesc('id')->get();

        return view('membership.agreements', compact('items'));
    }

    public function storeAgreement(Request $request)
    {
        $this->authorizeSettings();
        if ($this->boolInput($request, 'is_current')) {
            MembershipAgreement::query()->update(['is_current' => 0]);
        }
        MembershipAgreement::create([
            'version' => $request->input('version'),
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'is_current' => $this->boolInput($request, 'is_current'),
            'created_by' => Auth::id(),
        ]);

        return back()->with('message', 'Agreement saved.');
    }

    public function documents()
    {
        $this->authorizeMembership('memberships.documents');
        $items = MembershipDocument::with('application')->orderByDesc('id')->paginate(40);

        return view('membership.documents', compact('items'));
    }

    public function notifications()
    {
        $this->authorizeMembership();
        $items = MembershipNotification::with('membership.customer')->orderByDesc('id')->paginate(40);

        return view('membership.notifications', compact('items'));
    }

    public function reports()
    {
        $this->authorizeMembership('memberships.reports');
        $stats = $this->memberships->dashboardStats();
        $redemptions = MembershipBenefitRedemption::with(['membership'])->orderByDesc('id')->take(50)->get();

        return view('membership.reports', compact('stats', 'redemptions'));
    }

    public function audit()
    {
        $this->authorizeMembership();
        $items = MembershipAuditLog::orderByDesc('id')->paginate(50);

        return view('membership.audit', compact('items'));
    }

    public function settings()
    {
        $this->authorizeSettings();
        $percent = $this->memberships->memberDiscountPercent();
        $policy = $this->memberships->discountPolicy();
        $idTypes = MembershipSetting::get('id_doc_types', json_encode(['national_id', 'passport']));
        $remind = [
            'remind_30' => MembershipSetting::get('remind_30', '1'),
            'remind_7' => MembershipSetting::get('remind_7', '1'),
            'remind_1' => MembershipSetting::get('remind_1', '1'),
            'remind_on_expiry' => MembershipSetting::get('remind_on_expiry', '1'),
            'remind_after_expiry' => MembershipSetting::get('remind_after_expiry', '0'),
        ];

        return view('membership.settings', compact('percent', 'policy', 'idTypes', 'remind'));
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeSettings();
        $this->memberships->setMemberDiscountPercent($request->input('member_discount_percent', 10));
        MembershipSetting::put('discount_policy', $request->input('discount_policy', 'best'));
        MembershipSetting::put('id_doc_types', json_encode(array_values(array_filter((array) $request->input('id_doc_types', ['national_id', 'passport'])))));
        foreach (['remind_30', 'remind_7', 'remind_1', 'remind_on_expiry', 'remind_after_expiry'] as $key) {
            MembershipSetting::put($key, $this->boolInput($request, $key) ? '1' : '0');
        }

        return back()->with('message', 'Membership settings saved.');
    }
}
