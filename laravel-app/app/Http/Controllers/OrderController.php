<?php

namespace App\Http\Controllers;

use App\Booking;
use App\BookingProduct;
use App\Customer;
use App\GeneralSetting;
use App\Order;
use App\OrderProduct;
use App\paymentRequest;
use App\Product;
use App\Product_Warehouse;
use App\User;
use App\Warehouse;
use Doctrine\DBAL\Schema\AbstractAsset;
use App\Support\SchemaColumns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use NumberToWords\NumberToWords;
use Spatie\Permission\Models\Role;
use PDF;
use Twilio\TwiML\Voice\Pay;

class OrderController extends Controller
{
    protected function catalogOrders($vendorId = null)
    {
        $query = Order::query();
        if (SchemaColumns::has('orders', 'is_donation')) {
            $query->where('is_donation', 0);
        }
        if (SchemaColumns::has('orders', 'is_service')) {
            $query->where('is_service', 0);
        }
        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        return $query->orderByDesc('id');
    }

    public function index() {
        $vendor_id = null;
        if (Auth::user()->role_id == 12) {
            $vendor_id =  Auth::user()->id;
        }
        $data = $this->catalogOrders($vendor_id)->get();
        return view('order.index', compact('data'));
    }

    public function shopOrders($id) {
        $data = $this->catalogOrders($id)->get();
        return view('order.index', compact('data'));
    }

    public function paymentList() {
        $vendor_id = null;
        if (Auth::user()->role_id == 12) {
            $vendor_id =  Auth::user()->id;
        }
        if ($vendor_id == null) {
            $data = PaymentRequest::orderByDesc('id')->get();
        } else {
            $data = PaymentRequest::where('vendor_id', $vendor_id)->orderByDesc('id')->get();
        }
        return view('payment.index', compact('data'));
    }

    public function paymentListShop($id) {
        $vendor_id = $id;
        $data = PaymentRequest::where('vendor_id', $vendor_id)->orderByDesc('id')->get();
        return view('payment.index', compact('data'));
    }

    public function paymentDelete($id) {
        PaymentRequest::where('id', $id)->delete();
        return back()->with('not_permitted','Payment deleted successfully');
    }

    public function paymentEdit($id) {

        $data = PaymentRequest::find($id);
        return view('payment.edit', compact('data'));
    }

    public function paymentUpdate(Request $request, $id)
    {
        $data = PaymentRequest::find($id)->update(['status' => $request->status]);
        return back()->with('message','Payment Update successfully');
    }

    public function withdraw($id) {
        $data = Order::find($id);
        $vendor = User::where('id', $data->vendor_id)->first();
        $commission = $vendor->commission/100*$data->grand_total;
        $total = $data->grand_total;
        if($commission) {
            $total = $data->grand_total - $commission;
        }
        $payment = PaymentRequest::create([
            'vendor_id' => $data->vendor_id,
            'order_id' => $data->id,
            'amount' => $total,
            'status' => 0,
        ]);

        if ($payment) {
            $data->update(['payment_request' => 1]);
        }
        return back()->with('message','Payment Request is created successfully');
    }

    public function donationList() {
        $vendor_id = null;
        if (Auth::user()->role_id == 12) {
            $vendor_id =  Auth::user()->id;
        }
        $query = Order::query();
        if (SchemaColumns::has('orders', 'is_donation')) {
            $query->where('is_donation', 1);
        }
        if ($vendor_id) {
            $query->where('vendor_id', $vendor_id);
        }
        $data = $query->orderByDesc('id')->get();
        return view('order.donation-index', compact('data'));
    }

    public function serviceList() {
        $query = Order::query();
        if (SchemaColumns::has('orders', 'is_service')) {
            $query->where('is_service', 1);
        }
        $data = $query->orderByDesc('id')->get();
        return view('order.service-index', compact('data'));
    }

    public function show($id) {
        $data = Order::find($id);
        return view('order.show', compact('data'));
    }

    public function donationShow($id) {
        $data = Order::find($id);
        return view('order.donation-show', compact('data'));
    }

    public function serviceShow($id) {
        $data = Order::find($id);
        return view('order.service-show', compact('data'));
    }

    public function serviceDelete($id) {
        Order::where('id', $id)->delete();
        OrderProduct::where('order_id', $id)->delete();
        return back()->with('not_permitted','Service Order deleted successfully');
    }

    public function donationDelete($id) {
        Order::where('id', $id)->delete();
        OrderProduct::where('order_id', $id)->delete();
        return back()->with('not_permitted','Donation deleted successfully');
    }

    public function edit($id)
    {
        $data = Order::find($id);
        return view('order.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->except('id');
        if (isset($data['is_approve'])) {
            $data['is_approve'] = 1;
        } else {
            $data['is_approve'] = 0;
        }
        $order = Order::with('orderProducts')->find($id);

        if($order->order_status != 1 && $data['order_status'] == 1) {
            foreach ($order->orderProducts as $orderProducts) {
                $lims_product_data = Product::where('id', $orderProducts->product_id)->first();
                if($lims_product_data->type == 'standard') {
                    $warehouse_data = Product_Warehouse::where([
                        ['product_id', $lims_product_data->id],
                        ['warehouse_id', 1],
                    ])->first();

                    if($warehouse_data) {
                        $warehouse_data->qty -= $orderProducts->quantity;
                        $warehouse_data->save();
                    }

                    $lims_product_data->qty -= $orderProducts->quantity;
                    $lims_product_data->save();

                }
            }
        }

        if($order->order_status == 1 && $data['order_status'] != 1) {
            foreach ($order->orderProducts as $orderProducts) {
                $lims_product_data = Product::where('id', $orderProducts->product_id)->first();
                if($lims_product_data->type == 'standard') {
                    $warehouse_data = Product_Warehouse::where([
                        ['product_id', $lims_product_data->id],
                        ['warehouse_id', 1],
                    ])->first();

                    if($warehouse_data) {
                        $warehouse_data->qty += $orderProducts->quantity;
                        $warehouse_data->save();
                    }

                    $lims_product_data->qty += $orderProducts->quantity;
                    $lims_product_data->save();

                }
            }
        }

        if ($data['order_status'] == 0) {
            $status = 'Pending';
            $extra = '';
        } elseif ($data['order_status'] == 1) {
            $status = 'Completed';
            $extra = 'If you have received your order, please mark it as received in My Orders.';
        } elseif ($data['order_status'] == 2) {
            $status = 'Rejected';
            $extra = '';
        } else {
            $status = 'Ready For Delivery';
            $extra = ! empty($data['delivery_date']) ? 'Expected date: '.$data['delivery_date'] : '';
        }

        $lines = [];
        foreach ($order->orderProducts as $product) {
            $lines[] = [
                'name' => optional($product->product)->name ?? 'Item',
                'qty' => $product->quantity,
                'total' => $product->sub_total,
            ];
        }

        $msg = \App\Support\WhatsAppMessage::orderStatusUpdate(
            $order->name,
            $order->id,
            $status,
            $order->created_at,
            $order->grand_total,
            $order->payment_method,
            $order->address,
            $extra,
            $lines
        );

        try{
            $this->wpMessage($order->phone, $msg);
        }
        catch(\Exception $e){
        }
        $order->update($data);

        return redirect()->route('order.index')->with('message','Order Update successfully');
    }

    public function serviceUpdate(Request $request)
    {
        $data = $request->all();
        if (isset($data['is_approve'])) {
            $data['is_approve'] = 1;
        } else {
            $data['is_approve'] = 0;
        }
        $id = $request->id;
        $order = Order::find($id);

        if (isset($data['result_doc'])) {
            $image = $data['result_doc'];
            $imageName = date("Ymdhis").'.'.$image->getClientOriginalExtension();
            $image->move('public/images/customer/docs', $imageName);
            $data['result_doc'] = $imageName;
        }

        if ($data['order_status'] == 0) {
            $status = 'Pending';
            $extra = '';
        } elseif ($data['order_status'] == 1) {
            $status = 'Completed';
            $extra = 'If you have received your order, please mark it as received in My Orders.';
        } elseif ($data['order_status'] == 2) {
            $status = 'Rejected';
            $extra = '';
        } else {
            $status = 'Ready For Delivery';
            $extra = ! empty($data['delivery_date']) ? 'Expected date: '.$data['delivery_date'] : '';
        }

        $msg = \App\Support\WhatsAppMessage::orderStatusUpdate(
            $order->name,
            $order->id,
            $status,
            $order->created_at,
            $order->grand_total,
            $order->payment_method,
            $order->address,
            $extra
        );

        try{
            $this->wpMessage($order->phone, $msg);
        }
        catch(\Exception $e){
        }

        $order->update($data);

//        result doc
        $path = public_path('public/images/customer/docs/'.$order->result_doc);
        if($order->result_doc) {
            try{
                $this->wpAttachMessage($path, $order->phone, $order->result_doc);
            }
            catch(\Exception $e){
            }
        }

        return redirect()->route('services.list')->with('message','Service Update successfully');
    }

    public function delete($id)
    {
        Order::where('id', $id)->delete();
        OrderProduct::where('order_id', $id)->delete();
        return back()->with('not_permitted','Order deleted successfully');
    }

    public function deleteDoc($id)
    {
        $order = Order::find($id);
        $path = public_path('public/images/customer/docs/'.$order->result_doc);
        unlink($path);
        $order->update(['result_doc' => null]);
        return back()->with('not_permitted','Delivered Doc is deleted successfully');
    }


    public function frontendOrderIndex() {
        $query = Order::where('user_id', Auth::user()->id);
        if (SchemaColumns::has('orders', 'is_donation')) {
            $query->where('is_donation', 0);
        }
        if (SchemaColumns::has('orders', 'is_service')) {
            $query->where('is_service', 0);
        }
        $data = $query->orderByDesc('id')->paginate(5);
        return view('frontend.order_index', compact('data'));
    }

    public function frontendBookIndex() {
        $data = Booking::with('bookingProduct')->where('customer_id', Auth::user()->customer->id)->orderByDesc('id')->paginate(5);
        return view('frontend.book_index', compact('data'));
    }

    public function frontendDonationIndex() {
        $query = Order::where('user_id', Auth::user()->id);
        if (SchemaColumns::has('orders', 'is_donation')) {
            $query->where('is_donation', 1);
        }
        $data = $query->orderByDesc('id')->paginate(5);
        return view('frontend.donation_index', compact('data'));
    }

    public function frontendServiceIndex() {
        $query = Order::where('user_id', Auth::user()->id);
        if (SchemaColumns::has('orders', 'is_service')) {
            $query->where('is_service', 1);
        }
        $data = $query->orderByDesc('id')->paginate(5);
        return view('frontend.service_index', compact('data'));
    }

    public function frontendOrderTrack() {
        return view('frontend.order_track');
    }

    public function orderStatus(Request $request) {
        $order_status = Order::where('id', $request->id)->where('user_id', Auth::user()->id)->first();
        if($order_status) {
            return view('frontend.order_track', compact('order_status'));
        }
        $message = "You have enetered incorrect Order ID";
        return view('frontend.order_track', compact('message'));
    }

    public function generateInvoice($id)
    {
        $lims_sale_data = Order::find($id);
        $lims_product_sale_data = OrderProduct::where('order_id', $id)->get();
        $lims_customer_data = User::find($lims_sale_data->user_id);
        $lims_account_data = null;
        $lims_account_data_debit = null;
        $lims_account_data_cradit = null;


        $setting = GeneralSetting::first();
        $header = $setting->email_header;
        $footer = $setting->email_footer;
        $water_mark = $setting->email_water_mark;

        $numberToWords = new NumberToWords();
        if(\App::getLocale() == 'ar' || \App::getLocale() == 'hi' || \App::getLocale() == 'vi' || \App::getLocale() == 'en-gb')
            $numberTransformer = $numberToWords->getNumberTransformer('en');
        else
            $numberTransformer = $numberToWords->getNumberTransformer(\App::getLocale());
        $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);

        $data = [
            'header' => $header,
            'footer' => $footer,
            'water_mark' => $water_mark,
            'lims_account_data_cradit' => $lims_account_data_cradit,
            'lims_account_data_debit' => $lims_account_data_debit,
            'lims_sale_data' => $lims_sale_data,
            'lims_product_sale_data' => $lims_product_sale_data,
            'lims_customer_data' => $lims_customer_data,
            'numberInWords' => $numberInWords
        ];

//        return View('pdf.order_pdf', $data);
        return view('pdf.order_pdf', compact('header', 'footer', 'water_mark', 'lims_account_data_cradit', 'lims_account_data_debit', 'lims_sale_data', 'lims_product_sale_data', 'lims_customer_data', 'numberInWords'));


        $pdf = PDF::loadView('pdf.order_pdf', $data);
        return $pdf->download('order-invoice.pdf');
    }

    public function bookingGenerateInvoice($id)
    {
        if (!$this->canAccessBookingInvoice($id)) {
            abort(403, 'You are not authorized to download this invoice.');
        }

        $lims_sale_data = Booking::with('user', 'warehouse')->findOrFail($id);
        $lims_product_sale_data = BookingProduct::where('booking_id', $id)->get();
        $lims_customer_data = Customer::find($lims_sale_data->customer_id) ?: User::find($lims_sale_data->user_id);
        $lims_warehouse_data = Warehouse::find($lims_sale_data->warehouse_id);
        $lims_account_data = null;
        $lims_account_data_debit = null;
        $lims_account_data_cradit = null;


        $setting = GeneralSetting::first();
        $header = $setting->email_header;
        $footer = $setting->email_footer;
        $water_mark = $setting->email_water_mark;

        $numberToWords = new NumberToWords();
        if(\App::getLocale() == 'ar' || \App::getLocale() == 'hi' || \App::getLocale() == 'vi' || \App::getLocale() == 'en-gb')
            $numberTransformer = $numberToWords->getNumberTransformer('en');
        else
            $numberTransformer = $numberToWords->getNumberTransformer(\App::getLocale());
        $numberInWords = $numberTransformer->toWords($lims_sale_data->grand_total);

        $data = [
            'header' => $header,
            'footer' => $footer,
            'water_mark' => $water_mark,
            'lims_account_data_cradit' => $lims_account_data_cradit,
            'lims_account_data_debit' => $lims_account_data_debit,
            'lims_sale_data' => $lims_sale_data,
            'lims_product_sale_data' => $lims_product_sale_data,
            'lims_customer_data' => $lims_customer_data,
            'lims_warehouse_data' => $lims_warehouse_data,
            'numberInWords' => $numberInWords
        ];

//        return View('pdf.rent_pdf', $data);

        $pdf = PDF::loadView('pdf.rent_pdf', $data)->setPaper('A4', 'portrait');
        return $pdf->download('booking-invoice.pdf');
    }

    private function canAccessBookingInvoice($bookingId)
    {
        $booking = Booking::find($bookingId);
        if (!$booking) {
            return false;
        }

        if (session('booking_invoice_' . $bookingId) === true) {
            return true;
        }

        if (!Auth::check()) {
            return false;
        }

        $role = Role::find(Auth::user()->role_id);
        if ($role && $role->hasPermissionTo('booking_index')) {
            return true;
        }

        $customer = Customer::where('user_id', Auth::id())->first();

        return $customer && (int) $booking->customer_id === (int) $customer->id;
    }
}
