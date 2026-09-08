<?php

namespace App\Services;

class StripeService
{
    public function isConfigured()
    {
        return $this->secret() !== '' && $this->publicKey() !== '';
    }

    public function isTestMode()
    {
        return strtolower((string) config('services.stripe.mode', 'test')) !== 'live';
    }

    public function publicKey()
    {
        if ($this->isTestMode()) {
            return trim((string) config('services.stripe.key'));
        }

        $live = trim((string) config('services.stripe.live_key'));

        return $live !== '' ? $live : trim((string) config('services.stripe.key'));
    }

    public function secret()
    {
        if ($this->isTestMode()) {
            return trim((string) config('services.stripe.secret'));
        }

        $live = trim((string) config('services.stripe.live_secret'));

        return $live !== '' ? $live : trim((string) config('services.stripe.secret'));
    }

    public function webhookSecret()
    {
        return trim((string) config('services.stripe.webhook_secret'));
    }

    public function currency()
    {
        $currency = strtolower(trim((string) config('services.stripe.currency', 'rwf')));

        return $currency !== '' ? $currency : 'rwf';
    }

    /**
     * Zero-decimal currencies (RWF, UGX) are charged in whole units.
     */
    public function unitAmount($amount)
    {
        $int = (int) round($amount);
        $zeroDecimal = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];
        if (in_array($this->currency(), $zeroDecimal, true)) {
            return max(1, $int);
        }

        return max(1, (int) round($amount * 100));
    }

    public function createCheckoutSession(array $params)
    {
        $this->boot();
        $amount = $this->unitAmount(isset($params['amount']) ? $params['amount'] : 0);
        $name = isset($params['name']) ? $params['name'] : 'Welcome 2 Kigali';
        $successUrl = isset($params['success_url']) ? $params['success_url'] : url('/');
        $cancelUrl = isset($params['cancel_url']) ? $params['cancel_url'] : url('/');
        $payload = [
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => $this->currency(),
                    'product_data' => [
                        'name' => $name,
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ];
        if (! empty($params['client_reference_id'])) {
            $payload['client_reference_id'] = substr((string) $params['client_reference_id'], 0, 200);
        }
        if (! empty($params['customer_email'])) {
            $payload['customer_email'] = $params['customer_email'];
        }
        if (! empty($params['metadata']) && is_array($params['metadata'])) {
            $payload['metadata'] = $params['metadata'];
        }

        return \Stripe\Checkout\Session::create($payload);
    }

    public function retrieveSession($sessionId)
    {
        $this->boot();

        return \Stripe\Checkout\Session::retrieve($sessionId);
    }

    public function constructWebhookEvent($payload, $sigHeader)
    {
        $this->boot();
        $whsec = $this->webhookSecret();
        if ($whsec === '') {
            return json_decode($payload, true);
        }

        return \Stripe\Webhook::constructEvent($payload, $sigHeader, $whsec);
    }

    public function sessionIsPaid($session)
    {
        if (! $session) {
            return false;
        }
        $status = is_object($session) ? (isset($session->payment_status) ? $session->payment_status : '') : (isset($session['payment_status']) ? $session['payment_status'] : '');

        return $status === 'paid';
    }

    protected function boot()
    {
        \Stripe\Stripe::setApiKey($this->secret());
    }
}
