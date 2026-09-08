<?php

namespace App\Http\Controllers;

use App\BeyondUser;
use App\Membership;
use App\MembershipAgreement;
use App\MembershipPlan;
use App\MembershipPromotion;
use App\MembershipSetting;
use App\Services\BeyondAuthService;
use App\Services\MembershipNotifier;
use App\Services\MembershipService;
use App\Support\MembershipQr;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class MembershipPublicController extends Controller
{
    protected $memberships;
    protected $notifier;

    public function __construct(MembershipService $memberships, MembershipNotifier $notifier)
    {
        $this->memberships = $memberships;
        $this->notifier = $notifier;
    }

    public function apply()
    {
        $plans = MembershipPlan::active()->whereIn('code', ['monthly', 'quarterly', 'annual'])->get();
        if ($plans->isEmpty()) {
            $plans = MembershipPlan::active()->get();
        }
        $promo = MembershipPromotion::current();
        $agreement = MembershipAgreement::current();
        $idTypes = json_decode(MembershipSetting::get('id_doc_types', json_encode(['national_id', 'passport'])), true) ?: ['national_id', 'passport'];
        $discount = $this->memberships->memberDiscountPercent();
        $beyond = Auth::guard('beyond')->user();
        $scanToken = Session::get('membership_id_scan_token');
        if (! $scanToken) {
            $scanToken = \App\Support\MembershipIdScan::makeToken();
            Session::put('membership_id_scan_token', $scanToken);
        }
        \App\Support\MembershipIdScan::touch($scanToken);
        $scanUrl = \App\Support\MembershipIdScan::url($scanToken);
        $scanQrNational = \App\Support\OnlineInvitationQr::dataUri($scanUrl.'?type=national_id', 280);
        $scanQrPassport = \App\Support\OnlineInvitationQr::dataUri($scanUrl.'?type=passport', 280);
        $priorityCountries = \App\Support\MembershipCountries::priority();
        $otherCountries = \App\Support\MembershipCountries::others();

        return view('beyond.membership.apply', compact(
            'plans', 'promo', 'agreement', 'idTypes', 'discount', 'beyond',
            'scanToken', 'scanUrl', 'scanQrNational', 'scanQrPassport',
            'priorityCountries', 'otherCountries'
        ));
    }

    public function store(Request $request)
    {
        $idTypes = json_decode(MembershipSetting::get('id_doc_types', json_encode(['national_id', 'passport'])), true) ?: ['national_id', 'passport'];
        $beyond = Auth::guard('beyond')->user();
        $rules = [
            'plan_choice' => 'required|string',
            'full_name' => 'required|string|max:191',
            'email' => 'nullable|email|max:191',
            'phone' => 'required|string|max:40',
            'id_type' => 'required|in:'.implode(',', $idTypes),
            'id_number' => 'required|string|max:64',
            'id_expires_on' => 'nullable|date',
            'nationality' => 'required|string|max:80',
            'selfie' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:8192',
            'selfie_data' => 'nullable|string',
            'accept_agreement' => 'accepted',
            'signature' => 'required|string',
        ];
        if (! $beyond) {
            $rules['password'] = 'required|string|min:6';
        }
        $data = $request->validate($rules);

        $selfieFile = $request->file('selfie') ?: $this->uploadedFromDataUrl($request->input('selfie_data'), 'selfie');
        if (! $selfieFile) {
            return back()->withInput()->withErrors(['selfie' => __('site.membership.selfie_required')]);
        }

        $usePromo = $data['plan_choice'] === 'promo';
        if ($usePromo) {
            $planId = optional(MembershipPlan::where('code', 'monthly')->first())->id
                ?: optional(MembershipPlan::active()->first())->id;
        } else {
            $plan = MembershipPlan::active()->where('id', (int) $data['plan_choice'])->first();
            if (! $plan) {
                return back()->withInput()->withErrors(['plan_choice' => __('site.membership.choose_plan')]);
            }
            $planId = $plan->id;
        }

        $email = trim((string) ($data['email'] ?? ''));
        $digits = preg_replace('/\D/', '', $data['phone']);
        if ($email === '') {
            $email = ($digits ?: 'member').'@members.welcome2kigali.local';
        }

        $beyond = $this->ensureMemberAccount($beyond, $data['full_name'], $data['phone'], $email, $request->input('password'));

        $application = $this->memberships->submitApplication([
            'plan_id' => $planId,
            'use_promo' => $usePromo,
            'full_name' => $data['full_name'],
            'email' => $email,
            'phone' => $data['phone'],
            'id_type' => $data['id_type'],
            'id_number' => $data['id_number'] ?? null,
            'id_expires_on' => $data['id_expires_on'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'beyond_user_id' => $beyond ? $beyond->id : null,
            'submitted_ip' => $request->ip(),
        ], [
            'selfie' => $selfieFile,
        ], $data['signature']);

        try {
            $this->notifier->applicationReceived($application);
        } catch (\Throwable $e) {
        }

        return redirect()->route('membership.confirmation', $application->reference);
    }

    public function confirmation($reference)
    {
        $application = \App\MembershipApplication::where('reference', $reference)->firstOrFail();

        return view('beyond.membership.confirmation', compact('application'));
    }

    public function verify($token)
    {
        $membership = Membership::where('qr_token', $token)->with('customer')->first();

        return view('beyond.membership.verify', compact('membership'));
    }

    public function account()
    {
        $membership = $this->memberships->membershipForBeyondUser(Auth::guard('beyond')->user());
        if ($membership) {
            $membership->load(['customer', 'plan', 'payments', 'redemptions', 'application.documents', 'application.agreement']);
        }

        return view('beyond.membership.account', compact('membership'));
    }

    public function pdf(Membership $membership)
    {
        $this->authorizeMembershipAccess($membership);
        if (! $membership->confirmation_pdf) {
            abort(404);
        }
        $path = base_path('public/'.$membership->confirmation_pdf);
        if (! is_file($path)) {
            abort(404);
        }

        return response()->file($path);
    }

    public function renew($token)
    {
        $membership = Membership::where('renew_token', $token)->with(['customer', 'plan'])->firstOrFail();
        $plans = MembershipPlan::active()->get();

        return view('beyond.membership.renew', compact('membership', 'plans'));
    }

    public function startPayment(Request $request, $token)
    {
        $membership = Membership::where('renew_token', $token)->with('customer')->firstOrFail();
        $plan = MembershipPlan::findOrFail($request->input('plan_id'));
        $isRenewal = $membership->status !== 'APPROVED_PENDING_PAYMENT';
        $payment = $this->memberships->createPendingPayment($membership, $plan, $isRenewal);

        $payWith = strtolower((string) $request->input('pay_with', 'momo'));
        if ($payWith === 'visa' || $payWith === 'stripe' || $payWith === 'card') {
            return $this->redirectToStripe($membership, $payment);
        }

        return $this->redirectToMobileMoney($membership, $payment, $request->input('momo_network', 'mtn'), $request->input('momo_phone'));
    }

    public function paymentCheck(Request $request)
    {
        $status = strtoupper((string) $request->get('status'));
        $reference = $request->get('reference');
        $payment = null;
        if ($request->get('external_reference')) {
            $payment = \App\MembershipPayment::find($request->get('external_reference'));
        }
        if (! $payment && $reference) {
            $payment = \App\MembershipPayment::where('campay_reference', $reference)
                ->orWhere('reference', $reference)
                ->first();
        }
        if (! $payment) {
            return redirect('/membership/apply')->with('not_permitted', 'Payment was not found.');
        }
        if ($reference) {
            $payment->campay_reference = $reference;
            $payment->save();
        }
        if ($status === 'SUCCESSFUL') {
            $membership = $this->memberships->activateFromPayment($payment);
            try {
                $this->notifier->approved($membership);
            } catch (\Throwable $e) {
            }
            Session::put('membership_paid', $membership->id);

            return redirect()->route('membership.paid');
        }
        $payment->status = $status === 'FAILED' ? 'failed' : 'pending';
        $payment->save();

        return redirect(MembershipQr::renewUrl($payment->membership))->with('not_permitted', 'Payment was not completed.');
    }

    public function paid()
    {
        $id = Session::get('membership_paid');
        $membership = $id ? Membership::with(['customer', 'plan'])->find($id) : null;

        return view('beyond.membership.paid', compact('membership'));
    }

    protected function redirectToStripe(Membership $membership, $payment)
    {
        $stripe = app(\App\Services\StripePaymentService::class);
        if (! $stripe->isConfigured()) {
            return back()->with('not_permitted', 'Visa / card payments are not configured.');
        }
        $email = optional($membership->customer)->email;
        $result = $stripe->start(
            'membership',
            $payment->id,
            $payment->amount,
            'W2K Membership',
            \App\Support\MembershipQr::renewUrl($membership),
            ['email' => $email]
        );
        if (empty($result['ok'])) {
            return back()->with('not_permitted', $result['message'] ?? 'Could not start Visa payment.');
        }

        return redirect()->away($result['url']);
    }

    protected function redirectToCampay(Membership $membership, $payment)
    {
        return $this->redirectToMobileMoney($membership, $payment, 'mtn', null);
    }

    protected function redirectToMobileMoney(Membership $membership, $payment, $networkHint = 'mtn', $phoneOverride = null)
    {
        $phone = $phoneOverride ?: optional($membership->customer)->phone_number ?: optional($membership->application)->phone;
        $pawapay = app(\App\Services\PawaPayPaymentService::class);
        if ($pawapay->isConfigured()) {
            $result = $pawapay->start(
                'membership',
                $payment->id,
                $payment->amount,
                $phone,
                $networkHint,
                'W2K Membership',
                MembershipQr::renewUrl($membership)
            );
            if (empty($result['ok'])) {
                return back()->with('not_permitted', $result['message'] ?? 'Could not start Mobile Money payment.');
            }

            return redirect()->route('pawapay.wait', $result['deposit']->deposit_id);
        }

        $token = getenv('MOMO_TOKEN');
        $route = route('membership.payment.check');
        $link = $this->mobileMoneyRequestLink($token, (int) round($payment->amount), $route, $payment->id, $phone);
        if (! $link) {
            return back()->with('not_permitted', 'Could not start Campay payment. Please check the phone number and try again.');
        }

        return redirect()->away($link);
    }

    protected function ensureMemberAccount($beyond, $name, $phone, $email, $password)
    {
        $auth = app(BeyondAuthService::class);
        if ($beyond) {
            return $beyond;
        }

        $existing = $auth->findByPhone($phone);
        if ($existing) {
            Auth::guard('beyond')->login($existing);

            return $existing;
        }

        $username = $auth->normalizeUsername(preg_replace('/\D/', '', $phone));
        if ($username === '') {
            $username = 'member'.time();
        }
        $base = $username;
        $n = 1;
        while (BeyondUser::whereRaw('LOWER(username) = ?', [strtolower($username)])->exists()) {
            $username = $base.$n;
            $n++;
        }
        if (BeyondUser::whereRaw('LOWER(email) = ?', [strtolower($email)])->exists()) {
            $email = $username.'@members.welcome2kigali.local';
        }

        $user = BeyondUser::create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password_hash' => $auth->hashPassword($password),
            'role' => 'customer',
            'status' => 'active',
            'phone' => $phone,
            'must_change_credentials' => false,
        ]);
        $auth->syncProfile($user);
        Auth::guard('beyond')->login($user);

        return $user;
    }

    public function idScanPage(Request $request, $token)
    {
        $token = preg_replace('/[^A-Za-z0-9]/', '', (string) $token);
        $row = \App\Support\MembershipIdScan::touch($token);
        if (! $row) {
            abort(404);
        }

        $idType = $request->query('type');
        if (! in_array($idType, ['passport', 'national_id'], true)) {
            $idType = '';
        }

        return view('beyond.membership.id-scan', ['token' => $token, 'idType' => $idType]);
    }

    public function idScanStore(Request $request, $token)
    {
        $token = preg_replace('/[^A-Za-z0-9]/', '', (string) $token);
        if (strlen($token) < 16) {
            abort(404);
        }
        \App\Support\MembershipIdScan::complete($token, [
            'full_name' => trim((string) $request->input('full_name')),
            'id_number' => trim((string) $request->input('id_number')),
            'id_type' => $request->input('id_type'),
            'expires_on' => trim((string) $request->input('expires_on')),
            'nationality' => trim((string) $request->input('nationality')),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ready']);
        }

        return view('beyond.membership.id-scan-done');
    }

    public function idScanStatus($token)
    {
        $token = preg_replace('/[^A-Za-z0-9]/', '', (string) $token);
        $row = \App\Support\MembershipIdScan::get($token);
        if (! is_array($row)) {
            return response()->json(['status' => 'missing'], 404);
        }
        return response()->json($row);
    }

    protected function uploadedFromDataUrl($dataUrl, $basename)
    {
        $dataUrl = trim((string) $dataUrl);
        if ($dataUrl === '' || strpos($dataUrl, 'data:image/') !== 0) {
            return null;
        }
        if (! preg_match('#^data:image/(png|jpe?g|webp);base64,(.+)$#is', $dataUrl, $m)) {
            return null;
        }
        $raw = base64_decode($m[2], true);
        if ($raw === false || strlen($raw) < 32) {
            return null;
        }
        $ext = strtolower($m[1]);
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        $tmp = tempnam(sys_get_temp_dir(), 'w2k');
        file_put_contents($tmp, $raw);
        $mime = $ext === 'jpg' ? 'image/jpeg' : 'image/'.$ext;

        return new UploadedFile($tmp, $basename.'.'.$ext, $mime, UPLOAD_ERR_OK, true);
    }

    protected function authorizeMembershipAccess(Membership $membership)
    {
        $mine = $this->memberships->membershipForBeyondUser(Auth::guard('beyond')->user());
        if (! $mine || (int) $mine->id !== (int) $membership->id) {
            abort(403);
        }
    }
}
