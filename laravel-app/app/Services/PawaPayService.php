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

    public function createDeposit(array $payload)
    {
        return $this->request('POST', '/v2/deposits', $payload);
    }

    public function getDeposit($depositId)
    {
        if (! $depositId) {
            return null;
        }

        return $this->request('GET', '/v2/deposits/'.rawurlencode($depositId));
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
        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }
        $status = '';
        if (! empty($payload['data']['status'])) {
            $status = $payload['data']['status'];
        } elseif (! empty($payload['deposit']['status'])) {
            $status = $payload['deposit']['status'];
        } elseif (! empty($payload['status'])) {
            $status = $payload['status'];
        }
        $upper = strtoupper((string) $status);
        if ($upper === '' || $upper === 'FOUND' || $upper === 'NOT_FOUND') {
            return 'PENDING';
        }

        return $upper;
    }

    public function extractDepositId($payload)
    {
        if (! is_array($payload)) {
            return null;
        }
        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }
        foreach (['depositId', 'deposit_id'] as $key) {
            if (! empty($payload[$key])) {
                return (string) $payload[$key];
            }
        }
        if (! empty($payload['data']['depositId'])) {
            return (string) $payload['data']['depositId'];
        }

        return null;
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
