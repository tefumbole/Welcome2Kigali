<?php

namespace App\Services;

use App\Account;
use App\Biller;
use App\CashRegister;
use App\Customer;
use App\MembershipPayment;
use App\Order;
use App\PawaPayDeposit;
use App\Payment;
use App\Product;
use App\Product_Sale;
use App\Sale;
use App\Services\Messaging\NotificationRouter;
use App\Support\MomoNetwork;
use App\Support\VisitorLocale;
use App\Support\WhatsAppMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PawaPayPaymentService
{
    protected $api;

    public function __construct(PawaPayService $api)
    {
        $this->api = $api;
    }

    public function isConfigured()
    {
        return $this->api->isConfigured();
    }

    /**
     * @return array {ok: bool, deposit?: PawaPayDeposit, message?: string}
     */
    public function start($purpose, $purposeId, $amount, $phone, $networkHint, $description, $redirectUrl, array $extra = [])
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Mobile money is not configured. Add PAWAPAY_API_TOKEN to .env.'];
        }

        $msisdn = MomoNetwork::normalize($phone);
        if ($msisdn === '' || strlen($msisdn) < 10) {
            return ['ok' => false, 'message' => 'Please enter a valid MoMo phone number (e.g. 0793…).'];
        }

        $intAmount = (int) round($amount);
        if ($intAmount < 1) {
            return ['ok' => false, 'message' => 'Payment amount must be at least 1.'];
        }

        $reusable = PawaPayDeposit::where('purpose', $purpose)
            ->where('purpose_id', (int) $purposeId)
            ->where('status', 'pending')
            ->where('phone', $msisdn)
            ->where('amount', $intAmount)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->latest('id')
            ->first();
        if ($reusable) {
            return ['ok' => true, 'deposit' => $reusable];
        }

        $predicted = $this->api->predictProvider($msisdn);
        $provider = $predicted ?: MomoNetwork::provider($msisdn, $networkHint);
        $currency = isset($extra['currency']) ? $extra['currency'] : MomoNetwork::currencyForPhone($msisdn);
        $depositId = (string) Str::uuid();

        $payload = [
            'depositId' => $depositId,
            'amount' => (string) $intAmount,
            'currency' => $currency,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $msisdn,
                    'provider' => $provider,
                ],
            ],
            'customerMessage' => $this->api->customerMessage($description),
        ];

        $response = $this->api->createDeposit($payload);
        $status = $this->api->extractStatus($response);
        if (! $response || $this->api->isFailureStatus($status) || (! $this->api->isAcceptedStatus($status) && ! $this->api->isSuccessStatus($status))) {
            $failure = $this->api->failureMessage($response);
            Log::warning('PawaPay deposit rejected', ['status' => $status, 'body' => $response]);

            return [
                'ok' => false,
                'message' => $failure ?: 'Could not start Mobile Money. Check the number and try again.',
            ];
        }

        $row = PawaPayDeposit::create([
            'deposit_id' => $depositId,
            'purpose' => $purpose,
            'purpose_id' => (int) $purposeId,
            'extra_ids' => ! empty($extra['ids']) ? json_encode($extra['ids']) : null,
            'amount' => $amount,
            'currency' => $currency,
            'phone' => $msisdn,
            'provider' => $provider,
            'paying_method' => MomoNetwork::payingMethodLabel($provider),
            'status' => 'pending',
            'raw_status' => $status,
            'redirect_url' => $redirectUrl,
            'user_id' => Auth::id(),
        ]);

        if ($purpose === 'membership') {
            MembershipPayment::where('id', $purposeId)->update([
                'pawapay_deposit_id' => $depositId,
                'method' => 'PawaPay',
            ]);
        }

        if ($this->api->isSuccessStatus($status)) {
            $this->syncStatus($row, $status, $response);
        }

        return ['ok' => true, 'deposit' => $row->fresh()];
    }

    public function refreshAndApply($depositId)
    {
        $row = PawaPayDeposit::where('deposit_id', $depositId)->first();
        if (! $row) {
            return null;
        }
        if ($row->applied_at) {
            return $row;
        }
        $remote = $this->api->getDeposit($depositId);
        $status = $this->api->extractStatus($remote);
        $this->syncStatus($row, $status, $remote);

        return $row->fresh();
    }

    public function handleCallback(array $payload)
    {
        $depositId = $this->api->extractDepositId($payload);
        if (! $depositId) {
            Log::warning('PawaPay callback missing depositId', ['payload' => $payload]);

            return null;
        }
        $row = PawaPayDeposit::where('deposit_id', $depositId)->first();
        if (! $row) {
            Log::warning('PawaPay callback for unknown deposit', ['deposit_id' => $depositId]);

            return null;
        }
        if ($row->applied_at) {
            return $row;
        }

        $callbackStatus = $this->api->extractStatus($payload);
        $remote = $this->api->getDeposit($depositId);
        $remoteStatus = $this->api->extractStatus($remote);
        if ($this->api->isSuccessStatus($callbackStatus) && ! $this->api->isSuccessStatus($remoteStatus)) {
            Log::warning('PawaPay callback said paid but GET did not confirm', [
                'deposit_id' => $depositId,
                'callback' => $callbackStatus,
                'remote' => $remoteStatus,
            ]);
            $this->syncStatus($row, $remoteStatus ?: 'PENDING', $remote);

            return $row->fresh();
        }

        $this->syncStatus($row, $remoteStatus ?: $callbackStatus, $remote ?: $payload);

        return $row->fresh();
    }

    protected function syncStatus(PawaPayDeposit $row, $status, array $remote = null)
    {
        if ($row->applied_at) {
            return;
        }
        $row->raw_status = $status;
        if ($this->api->isSuccessStatus($status)) {
            $deposited = $this->api->extractDepositedAmount($remote);
            if ($deposited !== null && $deposited < 1) {
                Log::warning('PawaPay status completed but deposited amount is empty', [
                    'deposit_id' => $row->deposit_id,
                    'deposited' => $deposited,
                ]);
                $row->status = 'pending';
                $row->save();

                return;
            }
            $row->status = 'completed';
            $row->save();
            $this->apply($row);
        } elseif ($this->api->isFailureStatus($status)) {
            $row->status = 'failed';
            $row->save();
            if ($row->purpose === 'membership') {
                MembershipPayment::where('id', $row->purpose_id)->update(['status' => 'failed']);
            }
        } else {
            $row->status = 'pending';
            $row->save();
        }
    }

    public function apply(PawaPayDeposit $row)
    {
        if ($row->applied_at) {
            return $row;
        }
        if ($row->status !== 'completed') {
            return $row;
        }

        try {
            if ($row->purpose === 'membership') {
                $this->applyMembership($row);
            } elseif ($row->purpose === 'sale') {
                $this->applySale($row);
            } elseif ($row->purpose === 'order') {
                $this->applyOrders($row);
            } elseif ($row->purpose === 'booking') {
                $this->applyBooking($row);
            }
            $row->applied_at = now();
            $row->save();
        } catch (\Throwable $e) {
            Log::error('PawaPay apply failed: '.$e->getMessage(), ['deposit_id' => $row->deposit_id]);
        }

        return $row;
    }

    protected function applyMembership(PawaPayDeposit $row)
    {
        $payment = MembershipPayment::find($row->purpose_id);
        if (! $payment) {
            return;
        }
        $memberships = app(MembershipService::class);
        $membership = $memberships->activateFromPayment($payment);
        try {
            app(MembershipNotifier::class)->approved($membership);
        } catch (\Throwable $e) {
        }
    }

    protected function applySale(PawaPayDeposit $row)
    {
        $sale = Sale::find($row->purpose_id);
        if (! $sale) {
            return;
        }
        $already = Payment::where('sale_id', $sale->id)
            ->where('payment_note', 'like', '%'.$row->deposit_id.'%')
            ->first();
        if ($already) {
            return;
        }

        $due = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
        $amount = min((float) $row->amount, $due);
        if ($amount <= 0) {
            return;
        }

        $sale->paid_amount = $sale->paid_amount + $amount;
        $balance = $sale->grand_total - $sale->paid_amount;
        $sale->payment_status = $this->salePaymentStatus($sale->paid_amount, $balance);

        $cashRegister = CashRegister::where('user_id', $row->user_id ?: $sale->user_id)
            ->where('warehouse_id', $sale->warehouse_id)
            ->where('status', true)
            ->first();

        $payment = new Payment();
        $payment->user_id = $row->user_id ?: $sale->user_id;
        $payment->sale_id = $sale->id;
        if ($cashRegister) {
            $payment->cash_register_id = $cashRegister->id;
        }
        $account = Account::where('is_active', 1)->orderByDesc('is_default')->first();
        $payment->account_id = $account ? $account->id : 1;
        $payment->payment_reference = 'momo-'.date('Ymd-His').'-'.$sale->id;
        $payment->amount = $amount;
        $payment->change = 0;
        $payment->paying_method = $row->paying_method ?: 'MTN MoMo';
        $payment->payment_note = 'PawaPay '.$row->deposit_id;
        $payment->save();
        $sale->save();

        try {
            app(LaundryService::class)->createFromSale($sale->fresh('products'), '', [], false);
        } catch (\Throwable $e) {
            Log::warning('Laundry ticket after MoMo failed: '.$e->getMessage(), ['sale_id' => $sale->id]);
        }
        $fresh = $sale->fresh();
        $this->notifySalePaid($fresh, $row);
        try {
            app(\App\Http\Controllers\SaleController::class)->sendLaundryCategoryInvoice($fresh);
        } catch (\Throwable $e) {
            Log::warning('Laundry invoice after MoMo failed: '.$e->getMessage(), ['sale_id' => $sale->id]);
        }
    }

    protected function notifySalePaid(Sale $sale, PawaPayDeposit $row)
    {
        $customer = Customer::find($sale->customer_id);
        if (! $customer || ! $customer->phone_number) {
            return;
        }
        $lines = [];
        foreach (Product_Sale::where('sale_id', $sale->id)->get() as $line) {
            $product = Product::find($line->product_id);
            $lines[] = [
                'name' => $product ? $product->name : 'Item',
                'qty' => $line->qty,
                'unit_price' => $line->net_unit_price,
                'total' => $line->total,
            ];
        }
        $biller = Biller::find($sale->biller_id);
        $orderDate = $sale->created_at;
        if ($orderDate instanceof \DateTimeInterface) {
            $orderDate = $orderDate->format('D, M d, Y H:i');
        }
        try {
            $msg = WhatsAppMessage::withLocale(VisitorLocale::from($customer), function () use ($customer, $sale, $orderDate, $lines, $row, $biller) {
                return WhatsAppMessage::saleConfirmation(
                    $customer->name,
                    $sale->reference_no,
                    (string) $orderDate,
                    $lines,
                    $sale->grand_total,
                    $row->paying_method ?: 'MoMo',
                    $biller ? ($biller->name ?: $biller->company_name) : '',
                    $biller ? $biller->address : '',
                    $customer->address,
                    $row->currency ?: ''
                );
            });
            app(NotificationRouter::class)->sendWhatsAppText($customer->phone_number, $msg);
        } catch (\Throwable $e) {
            Log::warning('MoMo sale WhatsApp failed: '.$e->getMessage(), ['sale_id' => $sale->id]);
        }
    }

    protected function applyOrders(PawaPayDeposit $row)
    {
        $ids = $row->extraIds();
        if (empty($ids)) {
            $ids = [$row->purpose_id];
        }
        foreach ($ids as $id) {
            $order = Order::find($id);
            if (! $order) {
                continue;
            }
            $order->payment_status = 1;
            $order->payment_method = $row->paying_method ?: 'MTN';
            $order->save();
            try {
                app(\App\Services\Restaurant\RestaurantOrderService::class)->markWebsiteOrderPaid($order);
            } catch (\Throwable $e) {
                Log::warning('website restaurant paid: '.$e->getMessage());
            }
        }
    }

    protected function applyBooking(PawaPayDeposit $row)
    {
        $booking = \App\Booking::find($row->purpose_id);
        if (! $booking) {
            return;
        }
        $booking->payment_status = 4;
        $booking->paid_amount = $booking->grand_total;
        $booking->payment_method = $row->paying_method ?: 'MTN';
        $booking->save();
    }

    protected function salePaymentStatus($paid, $balance)
    {
        if ($paid <= 0) {
            return 1;
        }
        if ($balance > 0) {
            return 3;
        }

        return 4;
    }
}
