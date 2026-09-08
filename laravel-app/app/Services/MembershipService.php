<?php

namespace App\Services;

use App\Customer;
use App\CustomerGroup;
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
use App\Notifications\ContractWorkflowNotification;
use App\Product;
use App\Support\MembershipQr;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PDF;

class MembershipService
{
    const GROUP_NAME = 'Welcome to Kigali Members';

    public function memberGroup()
    {
        $id = MembershipSetting::get('member_group_id');
        if ($id) {
            $group = CustomerGroup::find($id);
            if ($group) {
                return $group;
            }
        }

        return CustomerGroup::where('name', self::GROUP_NAME)->first();
    }

    public function memberDiscountPercent()
    {
        $group = $this->memberGroup();
        if ($group) {
            return (float) $group->percentage;
        }

        return (float) MembershipSetting::get('member_discount_percent', 10);
    }

    public function setMemberDiscountPercent($percent)
    {
        $percent = max(0, min(100, (float) $percent));
        MembershipSetting::put('member_discount_percent', (string) $percent);
        $group = $this->memberGroup();
        if ($group) {
            $group->percentage = (string) $percent;
            $group->discount_mode = 'discount';
            $group->is_system = 1;
            $group->save();
        }
        $this->audit(null, null, 'settings.discount', ['percent' => $percent]);
    }

    public function discountPolicy()
    {
        return MembershipSetting::get('discount_policy', 'best');
    }

    public function activeMembershipForCustomer($customerId)
    {
        return Membership::where('customer_id', $customerId)
            ->whereIn('status', Membership::ACTIVE_STATUSES)
            ->orderByDesc('id')
            ->first();
    }

    public function applyToGroup(Membership $membership)
    {
        $group = $this->memberGroup();
        $customer = $membership->customer;
        if (! $group || ! $customer) {
            return;
        }
        if ((int) $customer->customer_group_id !== (int) $group->id) {
            $membership->previous_customer_group_id = $customer->customer_group_id;
            $membership->save();
        }
        $customer->customer_group_id = $group->id;
        $customer->save();
    }

    public function removeFromGroup(Membership $membership)
    {
        $group = $this->memberGroup();
        $customer = $membership->customer;
        if (! $customer) {
            return;
        }
        if ($group && (int) $customer->customer_group_id === (int) $group->id) {
            $fallback = $membership->previous_customer_group_id
                ?: optional(CustomerGroup::where('is_system', 0)->where('is_active', 1)->orderBy('id')->first())->id;
            $customer->customer_group_id = $fallback;
            $customer->save();
        }
    }

    public function syncGroupForStatus(Membership $membership)
    {
        if ($membership->isBenefitActive()) {
            $this->applyToGroup($membership);
        } else {
            $this->removeFromGroup($membership);
        }
    }

    public function nextNumber()
    {
        $year = date('Y');
        $prefix = 'WTK-M-'.$year.'-';
        $last = Membership::where('number', 'like', $prefix.'%')->orderByDesc('id')->value('number');
        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    public function newTokens()
    {
        return [
            'qr_token' => Str::random(40),
            'renew_token' => Str::random(40),
        ];
    }

    public function submitApplication(array $data, $files = [], $signature = null)
    {
        $agreement = MembershipAgreement::current();
        $promo = MembershipPromotion::current();
        $planId = ! empty($data['plan_id']) ? $data['plan_id'] : optional(MembershipPlan::active()->first())->id;

        $application = MembershipApplication::create([
            'reference' => $this->nextApplicationReference(),
            'plan_id' => $planId,
            'agreement_id' => $agreement ? $agreement->id : null,
            'promotion_id' => ! empty($data['use_promo']) && $promo && $promo->isLive() ? $promo->id : null,
            'beyond_user_id' => $data['beyond_user_id'] ?? null,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'company_name' => $data['company_name'] ?? null,
            'id_type' => $data['id_type'] ?? null,
            'id_number' => $data['id_number'] ?? null,
            'date_of_birth' => ! empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
            'id_expires_on' => ! empty($data['id_expires_on']) ? $data['id_expires_on'] : null,
            'nationality' => ! empty($data['nationality']) ? $data['nationality'] : null,
            'status' => 'PENDING',
            'signature_image' => $signature,
            'signed_at' => $signature ? Carbon::now() : null,
            'signed_agreement_version' => $agreement ? $agreement->version : null,
            'submitted_ip' => $data['submitted_ip'] ?? null,
        ]);

        $this->storeUploads($application, $files);
        $this->audit(null, $application->id, 'application.submitted', ['reference' => $application->reference]);

        return $application;
    }

    public function nextApplicationReference()
    {
        return 'WTK-A-'.date('Ymd').'-'.strtoupper(Str::random(5));
    }

    public function storeUploads(MembershipApplication $application, $files)
    {
        $dir = base_path('public/uploads/membership');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        foreach ($files as $type => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $name = 'm_'.$application->id.'_'.$type.'_'.time().'_'.Str::random(6).'.'.$ext;
            $file->move($dir, $name);
            MembershipDocument::create([
                'application_id' => $application->id,
                'doc_type' => $type,
                'path' => 'uploads/membership/'.$name,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }
    }

    public function findOrCreateCustomer(MembershipApplication $application)
    {
        $phone = preg_replace('/\D/', '', $application->phone);
        $customer = Customer::where('email', $application->email)->first();
        if (! $customer && $phone) {
            $customer = Customer::whereRaw("REPLACE(REPLACE(REPLACE(COALESCE(phone_number,''), '+', ''), ' ', ''), '-', '') LIKE ?", ['%'.substr($phone, -9).'%'])->first();
        }
        if (! $customer) {
            $walkIn = CustomerGroup::where('is_system', 0)->where('is_active', 1)->orderBy('id')->first();
            $customer = Customer::create([
                'customer_group_id' => $walkIn ? $walkIn->id : null,
                'name' => $application->full_name,
                'company_name' => $application->company_name,
                'email' => $application->email,
                'phone_number' => $application->phone,
                'address' => 'Kigali',
                'city' => 'Kigali',
                'is_active' => 1,
            ]);
        } else {
            $customer->name = $application->full_name;
            $customer->email = $application->email;
            $customer->phone_number = $application->phone;
            if ($application->company_name) {
                $customer->company_name = $application->company_name;
            }
            $customer->save();
        }
        if (empty($customer->user_id)) {
            $erp = \App\Support\UserWorkspaces::findExistingByPhoneOrEmail($application->phone, $application->email);
            if ($erp) {
                $customer->user_id = $erp->id;
                $customer->save();
            }
        }

        $application->customer_id = $customer->id;
        $application->save();

        return $customer;
    }

    public function approve(MembershipApplication $application, $userId = null)
    {
        $customer = $this->findOrCreateCustomer($application);
        $promo = $application->promotion_id
            ? MembershipPromotion::find($application->promotion_id)
            : MembershipPromotion::current();
        $usePromo = $promo && $promo->isLive();
        $tokens = $this->newTokens();

        $membership = $application->membership ?: new Membership();
        $membership->fill([
            'number' => $membership->number ?: $this->nextNumber(),
            'customer_id' => $customer->id,
            'application_id' => $application->id,
            'plan_id' => $application->plan_id,
            'promotion_id' => $usePromo ? $promo->id : null,
            'is_promotional' => $usePromo ? 1 : 0,
            'qr_token' => $membership->qr_token ?: $tokens['qr_token'],
            'renew_token' => $membership->renew_token ?: $tokens['renew_token'],
        ]);

        if ($usePromo) {
            $membership->status = 'ACTIVE';
            $membership->starts_at = Carbon::now();
            $membership->expires_at = Carbon::now()->addDays((int) $promo->free_days);
            $membership->save();
            $this->syncGroupForStatus($membership);
            $this->writeConfirmationPdf($membership);
        } else {
            $membership->status = 'APPROVED_PENDING_PAYMENT';
            $membership->save();
        }

        $application->status = $usePromo ? 'ACTIVE' : 'APPROVED_PENDING_PAYMENT';
        $application->save();

        $this->audit($membership->id, $application->id, 'application.approved', [
            'promotional' => $usePromo,
            'user_id' => $userId,
        ]);

        return $membership;
    }

    public function reject(MembershipApplication $application, $note = null, $userId = null)
    {
        $application->status = 'REJECTED';
        $application->admin_note = $note;
        $application->save();
        $this->audit(null, $application->id, 'application.rejected', ['note' => $note, 'user_id' => $userId]);
    }

    public function requestMoreInfo(MembershipApplication $application, $note, $userId = null)
    {
        $application->status = 'UNDER_REVIEW';
        $application->admin_note = $note;
        $application->save();
        $this->audit(null, $application->id, 'application.more_info', ['note' => $note, 'user_id' => $userId]);
    }

    public function activateFromPayment(MembershipPayment $payment)
    {
        $membership = $payment->membership;
        $plan = $payment->plan ?: $membership->plan;
        $now = Carbon::now();
        $start = $now;
        if ($membership->isBenefitActive() && $membership->expires_at && $membership->expires_at->gt($now)) {
            $start = $membership->expires_at->copy();
        }
        $months = $plan ? (int) $plan->duration_months : 1;
        $membership->plan_id = $plan ? $plan->id : $membership->plan_id;
        $membership->is_promotional = 0;
        $membership->status = 'ACTIVE';
        $membership->starts_at = $membership->starts_at ?: $now;
        $membership->expires_at = $start->copy()->addMonths($months);
        if (! $membership->renew_token) {
            $membership->renew_token = Str::random(40);
        }
        $membership->save();
        $this->syncGroupForStatus($membership);
        $this->writeConfirmationPdf($membership);
        $payment->status = 'paid';
        $payment->save();
        $this->audit($membership->id, $membership->application_id, $payment->is_renewal ? 'membership.renewed' : 'membership.activated', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
        ]);

        return $membership;
    }

    public function suspend(Membership $membership, $note = null)
    {
        $membership->status = 'SUSPENDED';
        $membership->save();
        $this->syncGroupForStatus($membership);
        $this->audit($membership->id, null, 'membership.suspended', ['note' => $note]);
    }

    public function unsuspend(Membership $membership)
    {
        $now = Carbon::now();
        $membership->status = ($membership->expires_at && $membership->expires_at->lte($now)) ? 'EXPIRED' : 'ACTIVE';
        if ($membership->expires_at && $membership->expires_at->gt($now) && $membership->expires_at->lte($now->copy()->addDays(30))) {
            $membership->status = 'EXPIRING';
        }
        $membership->save();
        $this->syncGroupForStatus($membership);
        $this->audit($membership->id, null, 'membership.unsuspended', []);
    }

    public function cancel(Membership $membership)
    {
        $membership->status = 'CANCELLED';
        $membership->save();
        $this->syncGroupForStatus($membership);
        $this->audit($membership->id, null, 'membership.cancelled', []);
    }

    public function processExpiry()
    {
        $now = Carbon::now();
        $soon = $now->copy()->addDays(30);
        Membership::whereIn('status', ['ACTIVE', 'EXPIRING'])->chunkById(100, function ($rows) use ($now, $soon) {
            foreach ($rows as $membership) {
                if ($membership->expires_at && $membership->expires_at->lte($now)) {
                    $membership->status = 'EXPIRED';
                    $membership->save();
                    $this->syncGroupForStatus($membership);
                    $this->audit($membership->id, null, 'membership.expired', []);
                } elseif ($membership->expires_at && $membership->expires_at->lte($soon) && $membership->status === 'ACTIVE') {
                    $membership->status = 'EXPIRING';
                    $membership->save();
                    $this->audit($membership->id, null, 'membership.expiring', []);
                }
            }
        });
    }

    public function posPayloadForCustomer($customerId)
    {
        $group = null;
        $customer = Customer::find($customerId);
        if ($customer) {
            $group = CustomerGroup::find($customer->customer_group_id);
        }
        $membership = $this->activeMembershipForCustomer($customerId);
        $percent = $group ? (float) $group->percentage : 0;
        $mode = $group && ! empty($group->discount_mode) ? $group->discount_mode : 'markup';

        return [
            'percentage' => $percent,
            'discount_mode' => $mode,
            'membership' => $membership ? [
                'id' => $membership->id,
                'number' => $membership->number,
                'status' => $membership->status,
                'expires_at' => optional($membership->expires_at)->toDateString(),
                'name' => optional($membership->customer)->name,
            ] : null,
            'policy' => $this->discountPolicy(),
        ];
    }

    public function benefitForProduct(Membership $membership, $productId)
    {
        if (! $membership->isBenefitActive()) {
            return null;
        }
        $benefit = MembershipProductBenefit::where('product_id', $productId)->where('is_active', 1)->first();
        if (! $benefit) {
            return null;
        }
        if (! $this->benefitAvailable($membership, $benefit)) {
            return null;
        }

        return $benefit;
    }

    public function benefitAvailable(Membership $membership, MembershipProductBenefit $benefit)
    {
        if ($benefit->frequency === 'unlimited') {
            return true;
        }
        $query = MembershipBenefitRedemption::where('membership_id', $membership->id)
            ->where('product_id', $benefit->product_id);
        $now = Carbon::now();
        if ($benefit->frequency === 'once_per_day') {
            $query->whereDate('redeemed_at', $now->toDateString());
        } elseif ($benefit->frequency === 'once_per_week') {
            $query->where('redeemed_at', '>=', $now->copy()->startOfWeek());
        } elseif ($benefit->frequency === 'once_per_month') {
            $query->where('redeemed_at', '>=', $now->copy()->startOfMonth());
        } elseif ($benefit->frequency === 'once_per_period') {
            $query->where('redeemed_at', '>=', $membership->starts_at ?: $now->copy()->subYears(10));
        }
        $used = (int) $query->sum('qty');

        return $used < max(1, (int) $benefit->qty);
    }

    public function recordRedemption(Membership $membership, $productId, $saleId, $qty, $value)
    {
        return MembershipBenefitRedemption::create([
            'membership_id' => $membership->id,
            'customer_id' => $membership->customer_id,
            'product_id' => $productId,
            'sale_id' => $saleId,
            'qty' => $qty,
            'value' => $value,
            'redeemed_at' => Carbon::now(),
        ]);
    }

    public function syncProductBenefit($productId, array $data)
    {
        $enabled = ! empty($data['membership_benefit']);
        $row = MembershipProductBenefit::firstOrNew(['product_id' => $productId]);
        if (! $enabled) {
            if ($row->exists) {
                $row->is_active = 0;
                $row->save();
            }

            return;
        }
        $row->kind = $data['membership_benefit_kind'] ?? 'free';
        $row->member_price = $data['membership_member_price'] ?? null;
        $row->qty = max(1, (int) ($data['membership_benefit_qty'] ?? 1));
        $row->frequency = $data['membership_benefit_frequency'] ?? 'unlimited';
        $row->is_active = 1;
        $row->save();
    }

    public function writeConfirmationPdf(Membership $membership)
    {
        $membership->load(['customer', 'plan']);
        $dir = base_path('public/uploads/membership');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = 'letter_'.$membership->number.'.pdf';
        try {
            $pdf = PDF::loadView('pdf.membership_confirmation', [
                'membership' => $membership,
                'verifyUrl' => MembershipQr::verifyUrl($membership),
            ]);
            $pdf->save($dir.'/'.$file);
            $membership->confirmation_pdf = 'uploads/membership/'.$file;
            $membership->save();
        } catch (\Throwable $e) {
            Log::warning('Membership PDF failed: '.$e->getMessage());
        }
    }

    public function createPendingPayment(Membership $membership, MembershipPlan $plan, $isRenewal = false)
    {
        return MembershipPayment::create([
            'membership_id' => $membership->id,
            'plan_id' => $plan->id,
            'amount' => $plan->fee,
            'method' => app(\App\Services\PawaPayPaymentService::class)->isConfigured() ? 'PawaPay' : 'Campay',
            'status' => 'pending',
            'reference' => 'WTK-P-'.date('YmdHis').'-'.$membership->id,
            'is_renewal' => $isRenewal ? 1 : 0,
        ]);
    }

    public function dashboardStats()
    {
        $now = Carbon::now();
        $soon = $now->copy()->addDays(30);
        $paid = MembershipPayment::where('status', 'paid');

        return [
            'total' => Membership::count(),
            'active' => Membership::where('status', 'ACTIVE')->count(),
            'expiring' => Membership::where('status', 'EXPIRING')->count()
                + Membership::where('status', 'ACTIVE')->whereBetween('expires_at', [$now, $soon])->count(),
            'expired' => Membership::where('status', 'EXPIRED')->count(),
            'suspended' => Membership::where('status', 'SUSPENDED')->count(),
            'pending_apps' => MembershipApplication::whereIn('status', ['PENDING', 'UNDER_REVIEW'])->count(),
            'promotional' => Membership::where('is_promotional', 1)->whereIn('status', Membership::ACTIVE_STATUSES)->count(),
            'paid_members' => Membership::where('is_promotional', 0)->whereIn('status', Membership::ACTIVE_STATUSES)->count(),
            'revenue' => (clone $paid)->where('is_renewal', 0)->sum('amount'),
            'renewal_revenue' => (clone $paid)->where('is_renewal', 1)->sum('amount'),
            'discounts_given' => 0,
            'free_value' => MembershipBenefitRedemption::sum('value'),
            'promo_to_paid' => $this->promoToPaidRate(),
        ];
    }

    private function promoToPaidRate()
    {
        $promoEver = Membership::where(function ($q) {
            $q->where('is_promotional', 1)->orWhereNotNull('promotion_id');
        })->count();
        if ($promoEver < 1) {
            return 0;
        }
        $converted = MembershipPayment::where('status', 'paid')->where('is_renewal', 1)
            ->whereHas('membership', function ($q) {
                $q->whereNotNull('promotion_id');
            })->distinct('membership_id')->count('membership_id');

        return round(($converted / $promoEver) * 100, 1);
    }

    public function sendReminders()
    {
        $notifier = app(MembershipNotifier::class);
        $now = Carbon::now();
        $windows = [];
        if (MembershipSetting::get('remind_30', '1') === '1') {
            $windows[] = ['kind' => 'd30', 'from' => $now->copy()->addDays(29)->startOfDay(), 'to' => $now->copy()->addDays(30)->endOfDay(), 'statuses' => ['ACTIVE', 'EXPIRING']];
        }
        if (MembershipSetting::get('remind_7', '1') === '1') {
            $windows[] = ['kind' => 'd7', 'from' => $now->copy()->addDays(6)->startOfDay(), 'to' => $now->copy()->addDays(7)->endOfDay(), 'statuses' => ['ACTIVE', 'EXPIRING']];
        }
        if (MembershipSetting::get('remind_1', '1') === '1') {
            $windows[] = ['kind' => 'd1', 'from' => $now->copy()->addDay()->startOfDay(), 'to' => $now->copy()->addDay()->endOfDay(), 'statuses' => ['ACTIVE', 'EXPIRING']];
        }
        foreach ($windows as $window) {
            Membership::whereIn('status', $window['statuses'])
                ->whereBetween('expires_at', [$window['from'], $window['to']])
                ->chunkById(50, function ($rows) use ($notifier, $window) {
                    foreach ($rows as $membership) {
                        $notifier->reminder($membership, $window['kind']);
                    }
                });
        }
        if (MembershipSetting::get('remind_on_expiry', '1') === '1') {
            Membership::where('status', 'EXPIRED')
                ->whereDate('expires_at', $now->toDateString())
                ->chunkById(50, function ($rows) use ($notifier) {
                    foreach ($rows as $membership) {
                        $notifier->reminder($membership, 'expired');
                    }
                });
        }
        if (MembershipSetting::get('remind_after_expiry', '0') === '1') {
            Membership::where('status', 'EXPIRED')
                ->whereDate('expires_at', $now->copy()->subDays(3)->toDateString())
                ->chunkById(50, function ($rows) use ($notifier) {
                    foreach ($rows as $membership) {
                        $notifier->reminder($membership, 'after');
                    }
                });
        }
    }

    public function membershipForBeyondUser($beyondUser)
    {
        if (! $beyondUser) {
            return null;
        }
        $app = MembershipApplication::where('beyond_user_id', $beyondUser->id)->orderByDesc('id')->first();
        if ($app && $app->membership) {
            return $app->membership;
        }
        $customer = null;
        if (! empty($beyondUser->email)) {
            $customer = Customer::where('email', $beyondUser->email)->first();
        }
        if (! $customer && ! empty($beyondUser->phone)) {
            $phone = preg_replace('/\D/', '', $beyondUser->phone);
            if ($phone) {
                $customer = Customer::whereRaw("REPLACE(REPLACE(REPLACE(COALESCE(phone_number,''), '+', ''), ' ', ''), '-', '') LIKE ?", ['%'.substr($phone, -9).'%'])->first();
            }
        }
        if (! $customer) {
            return null;
        }

        return Membership::where('customer_id', $customer->id)->orderByDesc('id')->first();
    }

    public function audit($membershipId, $applicationId, $action, array $meta = [])
    {
        MembershipAuditLog::create([
            'membership_id' => $membershipId,
            'application_id' => $applicationId,
            'user_id' => Auth::id(),
            'action' => $action,
            'meta' => json_encode($meta),
        ]);
    }

    public function notifyAdmins($message, $link = null)
    {
        $admins = User::where('role_id', '<=', 2)->where('is_active', 1)->get();
        foreach ($admins as $admin) {
            try {
                $admin->notify(new ContractWorkflowNotification($message, $link, 'membership'));
            } catch (\Throwable $e) {
            }
        }
    }
}
