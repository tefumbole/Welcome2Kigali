<?php

namespace App\Http\Controllers;

use App\Services\BeyondAuthService;
use App\Services\BeyondWasenderService;
use App\Support\CountryDialCodes;
use App\Support\MembershipCountries;
use App\Support\WhatsAppPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    protected $auth;
    protected $whatsapp;

    public function __construct(BeyondAuthService $auth, BeyondWasenderService $whatsapp)
    {
        $this->auth = $auth;
        $this->whatsapp = $whatsapp;
    }

    public function show(Request $request)
    {
        return view('beyond.reviews', [
            'reference' => $this->reference($request->query('ref')),
            'saved' => (bool) session('review_saved'),
            'verifyStep' => (bool) $request->session()->get('review_verify_phone'),
            'maskedPhone' => $request->session()->get('review_verify_masked'),
            'priorityCountries' => MembershipCountries::priority(),
            'otherCountries' => MembershipCountries::others(),
            'countryCodes' => CountryDialCodes::all(),
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('guest_reviews')) {
            return back()->withInput()->withErrors(['review' => __('site.reviews.unavailable')]);
        }

        $data = $this->validatedReview($request);
        $phone = $this->resolvePhone($data);

        if ($this->auth->shouldSkipOtp()) {
            $this->persistReview($data, $phone, now());

            return redirect()->route('beyond.reviews')->with('review_saved', true);
        }

        $request->session()->put('review_draft', array_merge($data, ['phone_full' => $phone]));

        $otp = $this->auth->createOtp($phone, 'guest_review');
        $send = $this->whatsapp->sendOtp($phone, $otp['code'], 'review');
        if (! ($send['success'] ?? false)) {
            return back()->withInput()->withErrors(['phone' => $send['error'] ?? __('site.reviews.otp_send_failed')]);
        }

        $request->session()->put('review_verify_phone', $otp['phone']);
        $request->session()->put('review_verify_masked', $this->whatsapp->maskPhone($otp['phone']));

        return redirect()->route('beyond.reviews')
            ->with('success', __('site.reviews.otp_sent'));
    }

    public function verify(Request $request)
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $phone = $request->session()->get('review_verify_phone');
        $draft = $request->session()->get('review_draft');
        if (! $phone || ! is_array($draft)) {
            return redirect()->route('beyond.reviews')->withErrors(['otp' => __('site.reviews.otp_expired')]);
        }

        $result = $this->auth->verifyOtp($phone, $request->otp, 'guest_review');
        if (! ($result['success'] ?? false)) {
            return redirect()->route('beyond.reviews')->withErrors(['otp' => $result['error'] ?? __('site.reviews.otp_invalid')]);
        }

        $this->persistReview($draft, $draft['phone_full'] ?? $phone, now());
        $request->session()->forget(['review_draft', 'review_verify_phone', 'review_verify_masked']);

        return redirect()->route('beyond.reviews')->with('review_saved', true);
    }

    public function resendOtp(Request $request)
    {
        $phone = $request->session()->get('review_verify_phone');
        if (! $phone) {
            return redirect()->route('beyond.reviews')->withErrors(['otp' => __('site.reviews.otp_expired')]);
        }

        $otp = $this->auth->createOtp($phone, 'guest_review');
        $send = $this->whatsapp->sendOtp($phone, $otp['code'], 'review');
        if (! ($send['success'] ?? false)) {
            return back()->withErrors(['otp' => $send['error'] ?? __('site.reviews.otp_send_failed')]);
        }

        $request->session()->put('review_verify_masked', $this->whatsapp->maskPhone($otp['phone']));

        return back()->with('success', __('site.reviews.otp_resent'));
    }

    protected function validatedReview(Request $request)
    {
        $data = $request->validate([
            'cleanliness' => 'required|integer|min:1|max:5',
            'on_time' => 'required|integer|min:1|max:5',
            'service' => 'required|integer|min:1|max:5',
            'food_quality' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'guest_name' => 'nullable|string|max:80',
            'reference' => 'nullable|string|max:80',
            'country' => 'required|string|max:80',
            'country_code' => 'required|string|max:8',
            'phone' => 'required|string|max:20',
            'signature' => 'required|string|max:500000',
        ]);

        if (! preg_match('/^data:image\/(png|jpeg);base64,/', (string) $data['signature'])) {
            throw ValidationException::withMessages([
                'signature' => [__('site.reviews.signature_required')],
            ]);
        }

        return $data;
    }

    protected function resolvePhone(array $data)
    {
        try {
            return WhatsAppPhone::combine($data['country_code'] ?? '+250', $data['phone'] ?? '');
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'phone' => [$e->getMessage()],
            ]);
        }
    }

    protected function persistReview(array $data, $phone, $verifiedAt)
    {
        $row = [
            'reference' => $this->reference($data['reference'] ?? ''),
            'guest_name' => trim((string) ($data['guest_name'] ?? '')) ?: null,
            'cleanliness' => (int) $data['cleanliness'],
            'on_time' => (int) $data['on_time'],
            'service' => (int) $data['service'],
            'food_quality' => (int) $data['food_quality'],
            'comment' => trim((string) ($data['comment'] ?? '')) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('guest_reviews', 'country')) {
            $row['country'] = trim((string) ($data['country'] ?? '')) ?: null;
        }
        if (Schema::hasColumn('guest_reviews', 'phone')) {
            $row['phone'] = $phone ?: null;
        }
        if (Schema::hasColumn('guest_reviews', 'phone_verified_at')) {
            $row['phone_verified_at'] = $verifiedAt;
        }
        if (Schema::hasColumn('guest_reviews', 'signature')) {
            $row['signature'] = $data['signature'] ?? null;
        }

        DB::table('guest_reviews')->insert($row);
    }

    protected function reference($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return substr(preg_replace('/[^\w\-\.\/]/', '', $value), 0, 80) ?: null;
    }
}
