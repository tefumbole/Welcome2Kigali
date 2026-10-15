<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PawaPayService
{
    public function isConfigured()
    {
        return trim((string) $this->token()) !== '';
    }

    public function token()
    {
        return (string) config('services.pawapay.api_token');
    }

    public function baseUrl()
    {
        $environment = strtolower((string) config('services.pawapay.environment', 'production'));
        if ($environment === 'sandbox' || $environment === 'test') {
            return rtrim((string) config('services.pawapay.sandbox_base_url', 'https://api.sandbox.pawapay.io'), '/');
        }

        return rtrim((string) config('services.pawapay.base_url', 'https://api.pawapay.io'), '/');
    }

    public function callbackUrl()
    {
        $fallback = rtrim((string) config('app.url'), '/').'/api/payments/pawapay/deposits/callback';
        $url = trim((string) config('services.pawapay.deposit_callback_url'));
        if ($url === '' || strpos($url, '${') !== false || stripos($url, 'http') !== 0) {
            return $fallback;
        }
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $callbackHost = parse_url($url, PHP_URL_HOST);
        if ($appHost && $callbackHost && strcasecmp($appHost, $callbackHost) !== 0) {
            return $fallback;
        }

        return $url;
    }

    public function createDeposit(array $payload)
    {
        unset($payload['callbackUrl']);
        $response = $this->request('POST', '/v2/deposits', $payload);
        if (! is_array($response) || $this->isUnsupportedParameter($response)) {
            Log::warning('PawaPay v2 deposit rejected, retrying v1', [
                'code' => $this->failureCode($response),
                'message' => $this->failureMessage($response),
            ]);
            $response = $this->request('POST', '/deposits', $this->v1DepositPayload($payload));
        }

        return $response;
    }

    public function getDeposit($depositId)
    {
        if (! $depositId) {
            return null;
        }

        $response = $this->request('GET', '/v2/deposits/'.rawurlencode($depositId));
        if (! is_array($response) || $this->shouldTryV1($response)) {
            $response = $this->request('GET', '/deposits/'.rawurlencode($depositId));
        }

        return $response;
    }

    public function failureMessage($payload)
    {
        if (! is_array($payload)) {
            return null;
        }
        if (! empty($payload['failureReason']['failureMessage'])) {
            return (string) $payload['failureReason']['failureMessage'];
        }
        if (! empty($payload['failureMessage'])) {
            return (string) $payload['failureMessage'];
        }
        if (! empty($payload['message']) && ! is_array($payload['message'])) {
            return (string) $payload['message'];
        }

        return null;
    }

    public function failureCode($payload)
    {
        if (! is_array($payload)) {
            return '';
        }

        return strtoupper((string) ($payload['failureReason']['failureCode'] ?? $payload['failureCode'] ?? ''));
    }

    protected function isUnsupportedParameter($payload)
    {
        return $this->failureCode($payload) === 'UNSUPPORTED_PARAMETER';
    }

    protected function shouldTryV1($payload)
    {
        if (! is_array($payload)) {
            return true;
        }
        $code = $this->failureCode($payload);
        if (in_array($code, ['UNSUPPORTED_PARAMETER', 'INVALID_SCHEMA', 'NOT_FOUND'], true)) {
            return true;
        }
        $message = strtolower((string) $this->failureMessage($payload));

        return strpos($message, 'callbackurl') !== false
            || strpos($message, 'unsupported parameter') !== false;
    }

    protected function v1DepositPayload(array $payload)
    {
        $phone = '';
        $provider = 'MTN_MOMO_RWA';
        if (! empty($payload['payer']['accountDetails']['phoneNumber'])) {
            $phone = (string) $payload['payer']['accountDetails']['phoneNumber'];
        } elseif (! empty($payload['payer']['address']['value'])) {
            $phone = (string) $payload['payer']['address']['value'];
        }
        if (! empty($payload['payer']['accountDetails']['provider'])) {
            $provider = (string) $payload['payer']['accountDetails']['provider'];
        } elseif (! empty($payload['correspondent'])) {
            $provider = (string) $payload['correspondent'];
        }

        return [
            'depositId' => $payload['depositId'],
            'amount' => (string) $payload['amount'],
            'currency' => $payload['currency'],
            'correspondent' => $provider,
            'payer' => [
                'type' => 'MSISDN',
                'address' => ['value' => $phone],
            ],
            'customerTimestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'statementDescription' => $this->customerMessage($payload['customerMessage'] ?? 'W2K Payment'),
        ];
    }

    public function predictProvider($phone)
    {
        $body = $this->request('POST', '/v2/predict-provider', ['phoneNumber' => $phone]);
        if (! is_array($body)) {
            return null;
        }
        $provider = isset($body['provider']) ? $body['provider'] : (isset($body['correspondent']) ? $body['correspondent'] : null);

        return $provider ? (string) $provider : null;
    }

    public function extractStatus($payload)
    {
        if (! is_array($payload)) {
            return 'PENDING';
        }
        $payload = $this->unwrapPayload($payload);
        $candidates = [
            $payload['data']['status'] ?? null,
            $payload['deposit']['status'] ?? null,
            $payload['depositStatus'] ?? null,
        ];
        $top = $payload['status'] ?? null;
        if (! $this->isEnvelopeStatus($top)) {
            $candidates[] = $top;
        }
        foreach ($candidates as $status) {
            $upper = $this->normalizeStatus($status);
            if ($upper !== 'PENDING') {
                return $upper;
            }
        }

        return 'PENDING';
    }

    public function extractDepositId($payload)
    {
        if (! is_array($payload)) {
            return null;
        }
        $payload = $this->unwrapPayload($payload);
        foreach (['depositId', 'deposit_id'] as $key) {
            if (! empty($payload[$key])) {
                return (string) $payload[$key];
            }
            if (! empty($payload['data'][$key])) {
                return (string) $payload['data'][$key];
            }
            if (! empty($payload['deposit'][$key])) {
                return (string) $payload['deposit'][$key];
            }
        }

        return null;
    }

    public function extractDepositedAmount($payload)
    {
        if (! is_array($payload)) {
            return null;
        }
        $payload = $this->unwrapPayload($payload);
        foreach (['depositedAmount', 'amount'] as $key) {
            if (isset($payload['data'][$key]) && is_numeric($payload['data'][$key])) {
                return (float) $payload['data'][$key];
            }
            if (isset($payload['deposit'][$key]) && is_numeric($payload['deposit'][$key])) {
                return (float) $payload['deposit'][$key];
            }
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return (float) $payload[$key];
            }
        }

        return null;
    }

    protected function unwrapPayload(array $payload)
    {
        if (isset($payload[0]) && is_array($payload[0])) {
            return $payload[0];
        }

        return $payload;
    }

    protected function normalizeStatus($status)
    {
        $upper = strtoupper(trim((string) $status));
        if ($upper === '' || $this->isEnvelopeStatus($upper)) {
            return 'PENDING';
        }

        return $upper;
    }

    protected function isEnvelopeStatus($status)
    {
        $upper = strtoupper(trim((string) $status));

        return $upper === ''
            || is_numeric($upper)
            || in_array($upper, ['FOUND', 'NOT_FOUND', 'OK'], true);
    }

    public function isSuccessStatus($status)
    {
        return in_array(strtoupper((string) $status), ['COMPLETED', 'SUCCESSFUL', 'SUCCESS'], true);
    }

    public function isFailureStatus($status)
    {
        return in_array(strtoupper((string) $status), ['FAILED', 'REJECTED', 'CANCELLED', 'CANCELED'], true);
    }

    public function isAcceptedStatus($status)
    {
        return in_array(strtoupper((string) $status), ['ACCEPTED', 'DUPLICATE_IGNORED', 'SUBMITTED', 'PENDING', 'IN_RECONCILIATION'], true);
    }

    public function customerMessage($description)
    {
        $message = preg_replace('/[^A-Za-z0-9 .\-]/', '', (string) $description);
        $message = trim($message) ?: 'W2K Payment';

        return substr($message, 0, 22);
    }

    protected function request($method, $path, array $payload = null)
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $url = $this->baseUrl().'/'.ltrim($path, '/');
        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => (int) config('services.pawapay.timeout', 30),
            CURLOPT_CONNECTTIMEOUT => (int) config('services.pawapay.connect_timeout', 10),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$this->token(),
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ];
        if ($payload !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($errno) {
            Log::warning('PawaPay connection error', ['path' => $path, 'error' => $error]);

            return null;
        }

        $body = json_decode((string) $response, true);
        if (! is_array($body)) {
            Log::warning('PawaPay non-JSON response', ['path' => $path, 'http' => $httpCode, 'body' => substr((string) $response, 0, 500)]);

            return null;
        }
        if ($httpCode >= 400 && empty($body['status'])) {
            Log::warning('PawaPay HTTP error', ['path' => $path, 'http' => $httpCode, 'body' => $body]);
        }

        return $body;
    }
}
