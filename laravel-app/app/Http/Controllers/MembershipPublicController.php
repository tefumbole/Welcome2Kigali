<?php

namespace App\Http\Controllers;

use App\Membership;
use App\MembershipAgreement;
use App\MembershipPlan;
use App\MembershipPromotion;
use App\MembershipSetting;
use App\Services\MembershipNotifier;
use App\Services\MembershipService;
use App\Support\MembershipQr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

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
        $plans = MembershipPlan::active()->get();
        $promo = MembershipPromotion::current();
        $agreement = MembershipAgreement::current();
        $idTypes = json_decode(MembershipSetting::get('id_doc_types', json_encode(['national_id', 'passport'])), true) ?: ['national_id', 'passport'];
        $discount = $this->memberships->memberDiscountPercent();

        return view('beyond.membership.apply', compact('plans', 'promo', 'agreement', 'idTypes', 'discount'));
    }

    public function store(Request $request)
    {
        $idTypes = json_decode(MembershipSetting::get('id_doc_types', json_encode(['national_id', 'passport'])), true) ?: ['national_id', 'passport'];
        $data = $request->validate([
            'plan_id' => 'nullable|integer',
            'full_name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'phone' => 'required|string|max:40',
            'company_name' => 'nullable|string|max:191',
            'id_type' => 'required|in:'.implode(',', $idTypes),
            'id_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:8192',
            'accept_agreement' => 'accepted',
            'signature' => 'required|string',
        ]);

        $beyond = Auth::guard('beyond')->user();
        $application = $this->memberships->submitApplication([
            'plan_id' => $data['plan_id'] ?? null,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'company_name' => $data['company_name'] ?? null,
            'id_type' => $data['id_type'],
            'beyond_user_id' => $beyond ? $beyond->id : null,
            'submitted_ip' => $request->ip(),
        ], [
            $data['id_type'] => $request->file('id_document'),
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

        return $this->redirectToCampay($membership, $payment);
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

    protected function redirectToCampay(Membership $membership, $payment)
    {
        $token = getenv('MOMO_TOKEN');
        $phone = optional($membership->customer)->phone_number ?: optional($membership->application)->phone;
        $route = route('membership.payment.check');
        $link = $this->mobileMoneyRequestLink($token, (int) round($payment->amount), $route, $payment->id, $phone);
        if (! $link) {
            return back()->with('not_permitted', 'Could not start Campay payment. Please check the phone number and try again.');
        }

        return redirect()->away($link);
    }

    protected function authorizeMembershipAccess(Membership $membership)
    {
        $mine = $this->memberships->membershipForBeyondUser(Auth::guard('beyond')->user());
        if (! $mine || (int) $mine->id !== (int) $membership->id) {
            abort(403);
        }
    }
}
