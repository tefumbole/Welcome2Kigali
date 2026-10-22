<?php

namespace App\Http\Controllers;

use App\RestaurantOrder;
use App\Sale;
use App\Support\MomoUssd;
use App\Services\Restaurant\RestaurantOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * JSON API for cafe orders placed from the public frontend.
 * MoMo still uses the QR + “I have paid” guest confirmation flow.
 */
class PublicOrderController extends Controller
{
    public function store(Request $request, RestaurantOrderService $orders)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:40',
            'service_type' => 'nullable|in:dine_in,take_away,eat_in,takeaway',
            'customer_comment' => 'nullable|string|max:1000',
            'checkout_token' => 'nullable|string|max:80',
            'payment_method' => 'nullable|in:momo,cod,visa,MTN,MOMO,COD,VISA,STRIPE',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.comment' => 'nullable|string|max:500',
            'restaurant_table_id' => 'nullable|integer',
        ]);

        $service = strtolower((string) ($data['service_type'] ?? 'take_away'));
        if (in_array($service, ['dine_in', 'eat_in'], true)) {
            $service = 'dine_in';
        } else {
            $service = 'take_away';
        }

        $token = trim((string) ($data['checkout_token'] ?? ''));
        if ($token === '') {
            $token = 'web-api-'.Str::uuid()->toString();
        }

        try {
            $sale = $orders->placePublic([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'service_type' => $service,
                'customer_comment' => $data['customer_comment'] ?? null,
                'checkout_token' => $token,
                'restaurant_table_id' => $data['restaurant_table_id'] ?? null,
                'items' => $data['items'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            \Log::warning('[public-order] place failed: '.$e->getMessage());

            return response()->json(['ok' => false, 'message' => 'Could not place the order.'], 500);
        }

        $method = strtoupper((string) ($data['payment_method'] ?? 'momo'));
        if (in_array($method, ['MTN', 'MOMO'], true)) {
            $method = 'MOMO';
        } elseif (in_array($method, ['VISA', 'STRIPE'], true)) {
            $method = 'VISA';
        } elseif ($method === 'COD') {
            $method = 'COD';
        } else {
            $method = 'MOMO';
        }

        return response()->json($this->payload($sale, $method));
    }

    public function show(Request $request, $saleId)
    {
        $sale = Sale::find($saleId);
        if (! $sale) {
            return response()->json(['ok' => false, 'message' => 'Order not found.'], 404);
        }
        if (! $this->authorized($request, $sale)) {
            return response()->json(['ok' => false, 'message' => 'Invalid token.'], 403);
        }

        return response()->json($this->payload($sale, 'MOMO'));
    }

    public function confirmPayment(Request $request, $saleId)
    {
        $sale = Sale::find($saleId);
        if (! $sale) {
            return response()->json(['ok' => false, 'message' => 'Order not found.'], 404);
        }
        if (! $this->authorized($request, $sale)) {
            return response()->json(['ok' => false, 'message' => 'Invalid token.'], 403);
        }

        $amount = MomoUssd::amount($sale->grand_total);
        $paymentToken = (string) $request->input('payment_token', '');
        $accessOk = hash_equals($this->accessToken($sale), (string) $request->input('access_token', ''));
        $momoOk = $paymentToken !== '' && MomoUssd::isValid('sale', $sale->id, $amount, $paymentToken);
        if (! $accessOk && ! $momoOk) {
            return response()->json(['ok' => false, 'message' => 'Invalid payment token.'], 403);
        }

        // Reuse the MoMo finalize path (QR + guest “I have paid”).
        app(MomoPayController::class)->confirmSalePaid($sale, $amount);

        $sale = $sale->fresh();

        return response()->json($this->payload($sale, 'MOMO'));
    }

    protected function payload(Sale $sale, $method)
    {
        $restaurant = Schema::hasTable('restaurant_orders')
            ? RestaurantOrder::where('sale_id', $sale->id)->first()
            : null;
        $amount = MomoUssd::amount($sale->grand_total);
        $paid = (int) $sale->payment_status === 4;
        $payUrl = MomoUssd::payUrl('sale', $sale->id, $amount);
        $momoToken = MomoUssd::token('sale', $sale->id, $amount);

        $out = [
            'ok' => true,
            'sale_id' => (int) $sale->id,
            'reference_no' => $sale->reference_no,
            'grand_total' => (float) $sale->grand_total,
            'payment_status' => $paid ? 'paid' : 'pending',
            'access_token' => $this->accessToken($sale),
            'track_url' => $restaurant ? $restaurant->trackUrl() : null,
            'payment' => [
                'method' => $method,
                'amount' => $amount,
                'pay_url' => $payUrl,
                'confirm_url' => url('/api/public/orders/'.$sale->id.'/payment/confirm'),
                'qr' => $paid ? null : MomoUssd::qrDataUri($amount),
                'ussd' => MomoUssd::code($amount),
                'payment_token' => $momoToken,
                'client_confirms' => true,
            ],
        ];

        if ($method === 'VISA') {
            try {
                $stripe = app(\App\Services\StripePaymentService::class);
                if ($stripe->isConfigured()) {
                    $result = $stripe->start(
                        'sale',
                        $sale->id,
                        $sale->grand_total,
                        'W2K Cafe',
                        url('/menu'),
                        ['email' => optional($sale->customer)->email]
                    );
                    if (! empty($result['ok'])) {
                        $out['payment']['pay_url'] = $result['url'];
                        $out['payment']['qr'] = null;
                        $out['payment']['client_confirms'] = false;
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('[public-order] stripe start failed: '.$e->getMessage());
            }
        }

        if ($method === 'COD') {
            $out['payment']['pay_url'] = null;
            $out['payment']['qr'] = null;
            $out['payment']['client_confirms'] = false;
        }

        return $out;
    }

    protected function accessToken(Sale $sale)
    {
        return substr(hash_hmac(
            'sha256',
            'public-order|'.$sale->id.'|'.$sale->reference_no,
            (string) config('app.key')
        ), 0, 32);
    }

    protected function authorized(Request $request, Sale $sale)
    {
        $token = (string) $request->input('access_token', $request->query('access_token', ''));
        if ($token !== '' && hash_equals($this->accessToken($sale), $token)) {
            return true;
        }
        $restaurant = RestaurantOrder::where('sale_id', $sale->id)->first();
        if ($restaurant && hash_equals((string) $restaurant->token, (string) $request->input('token', $request->query('token', '')))) {
            return true;
        }
        $checkout = (string) $request->input('checkout_token', $request->query('checkout_token', ''));
        if ($restaurant && $checkout !== '' && hash_equals((string) $restaurant->checkout_token, $checkout)) {
            return true;
        }

        return false;
    }
}
