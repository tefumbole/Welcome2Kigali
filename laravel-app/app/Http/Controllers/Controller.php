<?php

namespace App\Http\Controllers;

use App\BookingProduct;
use App\Customer;
use App\GeneralSetting;
use App\StockDuration;
use App\User;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;

class Controller extends BaseController
{


    private $user;

    public function __construct() {


        $this->middleware(function ($request, $next) {
            $this->user = \Illuminate\Support\Facades\Auth::user();
            if ($this->user && $this->user->role_id != 5) {
                $role = Role::find($this->user->role_id);
                $permissions = Role::findByName($role->name)->permissions;

                foreach ($permissions as $permission) {
                    $all_permission[] = $permission->name;
                }
                View::share ('all_permission', $all_permission);
            }
            return $next($request);
        });
    }

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private function whatsappConfig($key, $default = null)
    {
        return config('services.whatsapp.' . $key, $default);
    }

    private function whatsappServiceName()
    {
        return strtoupper((string) $this->whatsappConfig('service', 'WASENDER'));
    }

    private function usesWasender()
    {
        $service = $this->whatsappServiceName();
        if ($service === 'TWILIO') {
            return false;
        }
        if (in_array($service, ['ULTRAMSG', 'ULTRA'], true)) {
            return false;
        }

        return $service === 'WASENDER' || !empty($this->whatsappConfig('wasender_api_key'));
    }

    private function whatsappMessagingEnabled()
    {
        return filter_var($this->whatsappConfig('enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    /** Documents still go through Wasender when Twilio is selected but fallback is enabled. */
    private function usesWasenderForDocuments()
    {
        if ($this->usesWasender()) {
            return true;
        }

        if ($this->whatsappServiceName() !== 'TWILIO') {
            return false;
        }

        $fallback = filter_var($this->whatsappConfig('twilio_fallback_wasender', true), FILTER_VALIDATE_BOOLEAN);

        return $fallback && ! empty($this->whatsappConfig('wasender_api_key'));
    }

    private function assertWasenderConfigured()
    {
        if (empty($this->whatsappConfig('wasender_api_key'))) {
            throw new \Exception('WasenderAPI is not configured. Set WASENDER_API_KEY in Settings > .env Settings.');
        }

        if (empty($this->whatsappConfig('wasender_session_id'))) {
            throw new \Exception('WasenderAPI session is not configured. Set WASENDER_SESSION_ID (from Wasender dashboard → Sessions) in Settings > .env Settings.');
        }
    }

    private function wasenderBaseUrl()
    {
        return rtrim((string) $this->whatsappConfig('wasender_base_url', 'https://wasenderapi.com/api'), '/');
    }

    private function wasenderHttpHeaders()
    {
        return [
            'Authorization: Bearer ' . $this->whatsappConfig('wasender_api_key'),
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    private function wasenderApiRequest($method, $path, $payload = null)
    {
        $curl = curl_init();
        $options = [
            CURLOPT_URL => $this->wasenderBaseUrl() . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->wasenderHttpHeaders(),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ];

        if (strtoupper($method) === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload ?: []);
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($response === false || !empty($err)) {
            throw new \Exception('WasenderAPI request failed: ' . $err);
        }

        return json_decode($response, true);
    }

    private function wasenderRawRequest($path, $body, array $headers = [])
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->wasenderBaseUrl() . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array_merge([
                'Authorization: Bearer ' . $this->whatsappConfig('wasender_api_key'),
                'Accept: application/json',
            ], $headers),
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($response === false || !empty($err)) {
            throw new \Exception('WasenderAPI upload failed: ' . $err);
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : ['raw' => $response];
    }

    protected function resolveWhatsAppDocumentUrl($path)
    {
        $path = realpath($path) ?: $path;

        if (!is_file($path)) {
            throw new \Exception('Document file not found.');
        }

        if ($this->usesWasender()) {
            return $this->wasenderUploadLocalFile($path);
        }

        $storagePublic = realpath(storage_path('app/public'));
        if ($storagePublic && strpos($path, $storagePublic) === 0) {
            $relative = 'storage/' . ltrim(str_replace('\\', '/', substr($path, strlen($storagePublic))), '/');
            return url($relative);
        }

        $publicRoot = realpath(public_path());
        if ($publicRoot && strpos($path, $publicRoot) === 0) {
            $relative = ltrim(str_replace('\\', '/', substr($path, strlen($publicRoot))), '/');
            return url($relative);
        }

        throw new \Exception('Could not build a public URL for the document.');
    }

    protected function wasenderUploadLocalFile($path)
    {
        $this->assertWasenderConfigured();

        if (!is_file($path)) {
            throw new \Exception('Document file not found for upload.');
        }

        $mime = \App\Services\BeyondWasenderService::mimeTypeForPath($path);

        $decoded = $this->wasenderRawRequest('/upload', file_get_contents($path), [
            'Content-Type: ' . $mime,
        ]);

        if (!empty($decoded['publicUrl'])) {
            return $decoded['publicUrl'];
        }

        $message = $decoded['message'] ?? $decoded['error'] ?? 'Wasender upload did not return a public URL.';
        throw new \Exception($message);
    }

    protected function assertWasenderAttachmentSuccess($result, $phone = null)
    {
        if (!is_array($result)) {
            return;
        }

        if (isset($result['success']) && $result['success'] !== true) {
            $message = $result['message'] ?? $result['error'] ?? 'Wasender rejected the attachment';
            if ($phone && (stripos($message, 'JID') !== false || stripos($message, 'not exist on WhatsApp') !== false)) {
                $message = 'This phone number is not on WhatsApp or is invalid (' . $phone . '). Update the customer phone number and try again.';
            }
            throw new \Exception($message);
        }
    }

    private static $lastWhatsAppSendAt = 0.0;

    private static $lastWhatsAppSendType = null;

    protected function whatsappCompanyName()
    {
        $name = trim((string) $this->whatsappConfig('company_name', ''));
        if ($name !== '') {
            return $name;
        }

        $general = GeneralSetting::first();

        return $general->site_title ?? config('app.name', 'Application');
    }

    private function throttleWhatsAppSend()
    {
        $intervalMs = max(1000, (int) $this->whatsappConfig('min_send_interval_ms', 6000));
        $interval = $intervalMs / 1000;
        $now = microtime(true);

        if (self::$lastWhatsAppSendAt > 0) {
            $wait = $interval - ($now - self::$lastWhatsAppSendAt);
            if ($wait > 0) {
                usleep((int) round($wait * 1000000));
            }
        }

        self::$lastWhatsAppSendAt = microtime(true);
    }

    private function delayWasenderTextToDocument()
    {
        if (self::$lastWhatsAppSendType !== 'text') {
            return;
        }

        $delayMs = max(0, (int) $this->whatsappConfig('text_to_document_delay_ms', 6000));
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
            self::$lastWhatsAppSendAt = microtime(true);
        }
    }

    protected function normalizeWhatsAppPhone($number)
    {
        try {
            return \App\Support\WhatsAppPhone::forWasender($number);
        } catch (\InvalidArgumentException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function sendWasenderTextMessage($number, $msg)
    {
        $this->throttleWhatsAppSend();
        $this->assertWasenderConfigured();

        $payload = [
            'to' => $this->normalizeWhatsAppPhone($number),
            'text' => $msg,
        ];

        $decoded = $this->wasenderApiRequest('POST', '/send-message', $payload);

        if (is_array($decoded) && isset($decoded['success']) && $decoded['success'] !== true) {
            $message = $decoded['message'] ?? $decoded['error'] ?? 'WasenderAPI rejected the message';
            if (stripos($message, 'JID') !== false || stripos($message, 'not exist on WhatsApp') !== false) {
                $message = 'This phone number is not on WhatsApp or is invalid (' . $payload['to'] . '). Open the customer record, set the full mobile number (e.g. 675321739), and ensure it is registered on WhatsApp.';
            }
            throw new \Exception($message);
        }

        self::$lastWhatsAppSendType = 'text';

        return json_encode($decoded);
    }

    public function detectFileType($fileUrl)
    {
        $ext = strtolower(pathinfo(parse_url($fileUrl, PHP_URL_PATH), PATHINFO_EXTENSION));

        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $videoExts = ['mp4', 'mov', 'avi', 'mkv'];
        $audioExts = ['mp3', 'ogg', 'wav', 'aac'];
        $docExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

        if (in_array($ext, $imageExts)) {
            return 'image';
        }
        if (in_array($ext, $videoExts)) {
            return 'video';
        }
        if (in_array($ext, $audioExts)) {
            return 'audio';
        }
        if (in_array($ext, $docExts)) {
            return 'document';
        }

        return 'document';
    }

    public function wasenderAttachment($path, $lims_customer_data, $wa_path, $fileName)
    {
        $this->delayWasenderTextToDocument();
        $this->throttleWhatsAppSend();
        $this->assertWasenderConfigured();

        if (empty($wa_path)) {
            $wa_path = $this->resolveWhatsAppDocumentUrl($path);
        }

        $type = $this->detectFileType($path);
        $phone = $this->normalizeWhatsAppPhone($lims_customer_data->phone_number ?? $lims_customer_data->phone);
        $payload = [
            'to' => $phone,
            'fileName' => $fileName,
            'text' => $fileName,
        ];

        switch ($type) {
            case 'image':
                $payload['imageUrl'] = $wa_path;
                break;
            case 'video':
                $payload['videoUrl'] = $wa_path;
                break;
            case 'audio':
                $payload['audioUrl'] = $wa_path;
                break;
            default:
                $payload['documentUrl'] = $wa_path;
                break;
        }

        $result = $this->wasenderApiRequest('POST', '/send-message', $payload);
        $this->assertWasenderAttachmentSuccess($result, $phone);
        self::$lastWhatsAppSendType = 'document';

        return $result;
    }

    private function getUltraMsgConfig()
    {
        $instance = $this->whatsappConfig('ultramsg_instance');
        $token = $this->whatsappConfig('ultramsg_token');

        if (empty($instance) || empty($token)) {
            throw new \Exception('ULTRAMSG credentials are missing');
        }

        return [$instance, $token];
    }

    private function getCustomerPhoneNumber($customer)
    {
        $phone = $customer->phone_number ?? $customer->phone ?? null;
        if (empty($phone)) {
            throw new \Exception('Customer phone number is missing');
        }

        return $phone;
    }

    private function sendUltraMsgDocumentRequest($path, $to, $filename)
    {
        if (!file_exists($path)) {
            throw new \Exception('Attachment file not found');
        }

        list($instance, $token) = $this->getUltraMsgConfig();
        $data = file_get_contents($path);
        if ($data === false) {
            throw new \Exception('Unable to read attachment file');
        }

        $img_base64 = urlencode(base64_encode($data));
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ultramsg.com/$instance/messages/document",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "token=$token&to=$to&document=$img_base64&filename=$filename",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($response === false || !empty($err)) {
            throw new \Exception('WhatsApp API request failed');
        }

        $decodedResponse = json_decode($response, true);
        if (is_array($decodedResponse)) {
            if (!empty($decodedResponse['error'])) {
                throw new \Exception('WhatsApp API returned an error');
            }
            if (isset($decodedResponse['sent']) && (string) $decodedResponse['sent'] !== 'true') {
                throw new \Exception('WhatsApp API rejected the document');
            }
            if (isset($decodedResponse['status']) && in_array($decodedResponse['status'], array('error', 'failed'))) {
                throw new \Exception('WhatsApp API returned failed status');
            }
        }

        return true;
    }

    public function mobileMoneyRequestLink($token, $amount, $route, $patient_id, $number){

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.campay.net/api/get_payment_link/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "amount": "'.$amount.'",
                "from": "'.$number.'",
                "currency": "XAF",
                "external_reference": "'.$patient_id.'",
                "redirect_url": "'.$route.'",
                "payment_options":"MOMO,CARD",
                "failure_redirect_url": "'.$route.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Token ' . $token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $response_decode = json_decode($response, true);

        curl_close($curl);


        if($response_decode && isset($response_decode['link'])) {
            return $response_decode['link'];
        }
        return false;
    }

    public function mobileMoneyOrderRequestLink($token, $amount, $route, $orders, $failure_route, $number){

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.campay.net/api/get_payment_link/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "amount": "'.$amount.'",
                "from": "'.$number.'",
                "currency": "XAF",
                "external_reference": "'.$failure_route.',' .$orders.'",
                "redirect_url": "'.$route.'",
                "payment_options":"MOMO,CARD",
                "failure_redirect_url": "'.$route.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Token ' . $token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $response_decode = json_decode($response, true);

        curl_close($curl);

        if($response_decode && isset($response_decode['link'])) {
            return $response_decode['link'];
        }
        return false;
    }

    public function wpMessage($number, $msg){
        if (! $this->whatsappMessagingEnabled()) {
            \Log::info('[whatsapp] messaging disabled — skip wpMessage');
            return true;
        }

        $router = app(\App\Services\Messaging\NotificationRouter::class);
        $result = $router->sendWhatsAppText($number, $msg);

        if (! empty($result['success']) || ! empty($result['skipped'])) {
            return $result['sid'] ?? true;
        }

        // Legacy UltraMsg path when router could not send and provider is ULTRAMSG
        $service = $this->whatsappServiceName();
        if ($service === 'ULTRAMSG' || $service === 'ULTRA') {
            $this->throttleWhatsAppSend();

            list($instance, $token) = $this->getUltraMsgConfig();
            $params= [
                'token' => $token,
                'to' => $number,
                'body' => $msg
            ];
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.ultramsg.com/".$instance."/messages/chat",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => http_build_query($params),
                CURLOPT_HTTPHEADER => array(
                    "content-type: application/x-www-form-urlencoded"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($response === false || !empty($err)) {
                throw new \Exception('WhatsApp message sending failed');
            }

            return true;
        }

        throw new \Exception($result['error'] ?? 'WhatsApp message sending failed. Check Settings → Messaging Settings.');
    }

    protected function sendWhatsAppToCustomer($customer, $msg)
    {
        $phone = $this->getCustomerPhoneNumber($customer);
        return $this->wpMessage($phone, $msg);
    }

    protected function sendWhatsAppToPhone($phone, $msg)
    {
        if (empty(trim((string) $phone))) {
            throw new \Exception('Phone number is missing');
        }

        return $this->wpMessage($phone, $msg);
    }

    protected function sendWhatsAppDocumentToCustomer($customer, $path, $filename, $publicUrl = null)
    {
        if (! $this->whatsappMessagingEnabled()) {
            \Log::info('[whatsapp] messaging disabled — skip document');
            return true;
        }

        if ($this->usesWasenderForDocuments()) {
            $this->assertWasenderConfigured();
            if (empty($publicUrl)) {
                // Force Wasender upload path even when WHATSAPP_SERVICE=TWILIO
                $publicUrl = $this->wasenderUploadLocalFile($path);
            }

            return $this->wasenderAttachment($path, $customer, $publicUrl, $filename);
        }

        $service = $this->whatsappServiceName();
        if ($service !== 'ULTRAMSG' && $service !== 'ULTRA') {
            throw new \Exception('WhatsApp attachments use WasenderAPI. Keep Wasender keys set (fallback) or set WHATSAPP_SERVICE=WASENDER in Messaging Settings.');
        }

        $phone = $this->getCustomerPhoneNumber($customer);
        return $this->sendUltraMsgDocumentRequest($path, $phone, $filename);
    }

    protected function sendWhatsAppDocumentToPhone($phone, $path, $filename, $publicUrl = null)
    {
        if (! $this->whatsappMessagingEnabled()) {
            \Log::info('[whatsapp] messaging disabled — skip document');
            return true;
        }

        if ($this->usesWasenderForDocuments()) {
            $this->assertWasenderConfigured();
            if (empty($publicUrl)) {
                $publicUrl = $this->wasenderUploadLocalFile($path);
            }

            $recipient = (object) ['phone_number' => $phone];
            return $this->wasenderAttachment($path, $recipient, $publicUrl, $filename);
        }

        $service = $this->whatsappServiceName();
        if ($service !== 'ULTRAMSG' && $service !== 'ULTRA') {
            throw new \Exception('WhatsApp attachments use WasenderAPI. Keep Wasender keys set (fallback) or set WHATSAPP_SERVICE=WASENDER in Messaging Settings.');
        }

        return $this->sendUltraMsgDocumentRequest($path, $phone, $filename);
    }


    public function wpAttachMessage($path, $number, $filename='compile_result.pdf', $wa_path = null){
        if ($this->usesWasender()) {
            $this->assertWasenderConfigured();
            if (empty($wa_path)) {
                $wa_path = $this->resolveWhatsAppDocumentUrl($path);
            }
            $customer = (object) ['phone_number' => $number];
            return $this->wasenderAttachment($path, $customer, $wa_path, $filename);
        }

        $service = $this->whatsappServiceName();
        if ($service !== 'ULTRAMSG' && $service !== 'ULTRA') {
            throw new \Exception('WhatsApp attachments use WasenderAPI. Set WHATSAPP_SERVICE=WASENDER, WASENDER_API_KEY, and WASENDER_SESSION_ID in your environment settings.');
        }

        return $this->sendUltraMsgDocumentRequest($path, $number, $filename);
    }


    public function wpPDFMessage($path, $lims_customer_data, $filename='invoice.pdf', $wa_path = null, $captionOverride = null){
        if ($this->usesWasender()) {
            $this->assertWasenderConfigured();
            if (empty($wa_path)) {
                $wa_path = $this->resolveWhatsAppDocumentUrl($path);
            }

            $customerName = $lims_customer_data->name ?? 'Customer';
            $caption = $captionOverride !== null && $captionOverride !== ''
                ? $captionOverride
                : (\App\Support\WhatsAppMessage::statusBlock('📄', 'DOCUMENT ATTACHED')
                    . \App\Support\WhatsAppMessage::greeting($customerName)
                    . 'Please find your *' . $filename . '* attached.'
                    . \App\Support\WhatsAppMessage::footer());

            try {
                $this->sendWasenderTextMessage(
                    $lims_customer_data->phone_number ?? $lims_customer_data->phone,
                    $caption
                );
            } catch (\Exception $e) {
            }

            return $this->wasenderAttachment($path, $lims_customer_data, $wa_path, $filename);
        }

        $service = $this->whatsappServiceName();
        if ($service !== 'ULTRAMSG' && $service !== 'ULTRA') {
            throw new \Exception('WhatsApp attachments use WasenderAPI. Set WHATSAPP_SERVICE=WASENDER, WASENDER_API_KEY, and WASENDER_SESSION_ID in your environment settings.');
        }

        $to = $this->getCustomerPhoneNumber($lims_customer_data);
        return $this->sendUltraMsgDocumentRequest($path, $to, $filename);
    }

    public function wpPDFAnnouncement($path, $lims_customer_data, $filename='invoice.pdf', $wa_path = null){
        // Announcement attachments always use Wasender (documents are not Twilio Content Templates).
        if (! $this->whatsappMessagingEnabled()) {
            \Log::info('[whatsapp] messaging disabled — skip announcement document');
            return true;
        }

        $this->assertWasenderConfigured();

        if (! is_file($path)) {
            $candidate = public_path(ltrim((string) $path, '/'));
            if (is_file($candidate)) {
                $path = $candidate;
            }
        }

        if (empty($wa_path)) {
            $wa_path = $this->wasenderUploadLocalFile($path);
        }

        return $this->wasenderAttachment($path, $lims_customer_data, $wa_path, $filename);
    }

    public function sendWhatsappMsgForPlacingOrderToBuyer($order){

        $lines = [];
        if (! empty($order->orderProducts)) {
            foreach ($order->orderProducts as $product) {
                $lines[] = [
                    'name' => optional($product->product)->name ?? 'Item',
                    'qty' => $product->quantity,
                    'unit_price' => $product->price,
                    'total' => $product->sub_total,
                ];
            }
        }

        $msg = \App\Support\WhatsAppMessage::withLocale(\App\Support\VisitorLocale::from($order), function () use ($order, $lines) {
            return \App\Support\WhatsAppMessage::saleConfirmation(
                $order->name,
                $order->id,
                $order->created_at,
                $lines,
                $order->grand_total,
                $order->payment_method,
                '',
                $order->address,
                $order->address
            );
        });

        try{
            $this->wpMessage($order->phone, $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function sendWhatsappMsgForPlacingServiceToBuyer($order){

        $serviceName = '';
        if (! empty($order->orderProducts) && $order->orderProducts->first() && $order->orderProducts->first()->product) {
            $serviceName = $order->orderProducts->first()->product->name;
        }

        $msg = \App\Support\WhatsAppMessage::compose(
            '🧾',
            'SERVICE ORDER CONFIRMATION',
            $order->name,
            'Thank you for your service order with *'.\App\Support\WhatsAppMessage::companyName().'*.',
            [
                'Order Number' => $order->id,
                'Order Date' => $order->created_at,
                'Service' => $serviceName,
                'Subject' => $order->subject,
                'Project' => $order->project_title,
                'Total' => number_format((float) $order->grand_total, 2),
                'Payment' => $order->payment_method,
                'Delivery' => $order->address,
            ],
            $order->payment_method == 'COD' ? 'Payment is cash on delivery. Our team will confirm your order.' : ''
        );

        try{
            $this->wpMessage($order->phone, $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function sendWhatsappMsgForPlacingServiceToSaller($order){

        $serviceName = '';
        if (! empty($order->orderProducts) && $order->orderProducts->first() && $order->orderProducts->first()->product) {
            $serviceName = $order->orderProducts->first()->product->name;
        }

        $msg = \App\Support\WhatsAppMessage::compose(
            '🧾',
            'NEW SERVICE ORDER',
            'Team',
            'A new service order has been placed.',
            [
                'Client' => $order->name,
                'Phone' => $order->phone,
                'Order Number' => $order->id,
                'Service' => $serviceName,
                'Subject' => $order->subject,
                'Total' => number_format((float) $order->grand_total, 2),
                'Payment' => $order->payment_method,
            ]
        );

        try{
            $this->wpMessage(getenv('ADMIN_NUMBER'), $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }


    public function sendWhatsappMsgMomoPaymentSuccess($number, $total)
    {
        $msg = \App\Support\WhatsAppMessage::paymentReceived('', $total);

        try{
            $this->wpMessage($number, $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function sendWhatsappMsgForPlacingOrderToBuyerBooking($order){

        $customer = User::where('id', $order->user_id)->first();
        $lines = [];
        $bookingProducts = BookingProduct::with('product')->where('booking_id', $order->id)->get();
        foreach ($bookingProducts as $product) {
            $lines[] = [
                'name' => optional($product->product)->name ?? 'Item',
                'qty' => $product->qty,
                'start' => $product->start,
                'end' => $product->end,
                'total' => number_format((float) ($product->qty * $product->number_duration * $product->net_unit_price), 2),
            ];
        }

        $msg = \App\Support\WhatsAppMessage::bookingConfirmation(
            $customer ? $customer->name : 'Guest',
            $order->id,
            $order->created_at,
            $lines,
            number_format((float) $order->grand_total, 2),
            $order->payment_method,
            \App\Support\WhatsAppMessage::companyName(),
            $order->address,
            $customer ? $customer->phone : $order->phone
        );

        try{
            $this->wpMessage($customer ? $customer->phone : $order->phone, $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function sendWhatsappMsgForPlacingOrderToAdminBooking($order){
        // Notify the staff user who owns/created the booking — not a global admin number.
        $creator = ! empty($order->user_id) ? User::find($order->user_id) : null;
        if (! $creator || empty(trim((string) $creator->phone))) {
            return true;
        }

        $customer = Customer::where('id', $order->customer_id)->first();
        $customerName = $customer ? $customer->name : 'Client';

        $msg = \App\Support\WhatsAppMessage::compose(
            '📋',
            'BOOKING COPY',
            $creator->name,
            'Here is a copy of the booking you recorded for *'.$customerName.'*.',
            [
                'Order Number' => $order->id,
                'Order Date' => $order->created_at,
                'Client' => $customerName,
                'Total' => number_format((float) $order->grand_total, 2),
                'Payment' => $order->payment_method,
            ]
        );

        try{
            $this->wpMessage($creator->phone, $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function sendWhatsappMsgForPlacingOrderToSaller($order){

        $vendor = User::where('id', $order->vendor_id)->first();
        $msg = \App\Support\WhatsAppMessage::compose(
            '🧾',
            'NEW SALE ORDER',
            $vendor ? $vendor->name : 'Team',
            'You have received an order from *'.$order->name.'*.',
            [
                'Client' => $order->name,
                'Phone' => $order->phone,
                'Order Number' => $order->id,
                'Order Date' => $order->created_at,
                'Total' => number_format((float) $order->grand_total, 2),
                'Payment' => $order->payment_method,
                'Delivery' => $order->address,
            ]
        );

        try{
            if ($vendor && $vendor->phone) {
                $this->wpMessage($vendor->phone, $msg);
            }
            $this->wpMessage(getenv('ADMIN_NUMBER'), $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function sendWhatsappMsgMomoPaymentSuccessDonation($general_setting, $order, $total)
    {
        $user = User::select('name', 'id', 'phone')->find($order->user_id);
        $msg = \App\Support\WhatsAppMessage::donationReceived($order->name ?: optional($user)->name, $total);

        try{
            $this->wpMessage($user->phone, $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function sendWhatsappMsgMomoPaymentSuccessDonationSeller($general_setting, $order)
    {
        $user = User::select('name', 'id', 'phone')->find($order->vendor_id);
        $amount = number_format((float) $order->grand_total, 2);

        if ($user && $user->phone) {
            $msg = \App\Support\WhatsAppMessage::donationReceived($user->name, $amount, true, $order->name);
            try{
                $this->wpMessage($user->phone, $msg);
            }
            catch(\Exception $e){

            }
        }

        $msg = \App\Support\WhatsAppMessage::donationReceived('Team', $amount, true, $order->name);
        try{
            $this->wpMessage(getenv('ADMIN_NUMBER'), $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function sendOTP($phone) {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $msg = \App\Support\WhatsAppMessage::otpMessage($otp);
        try {
            $this->wpMessage($phone, $msg);
        } catch (\Exception $e) {
            return $otp;
        }
        return $otp;
    }


    public function sendWhatsappMsgForAccount($user, $password){

        $msg = \App\Support\WhatsAppMessage::accountCreated(
            $user->name,
            $user->phone,
            $password,
            url('/login')
        );

        try{
            $this->wpMessage($user->phone, $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function sendWhatsappMsgForVendorAccount($user, $password){

        $note = 'Your account is under review. After approval you can sell products. Commission: '.$user->commission.'% per sale.';
        $msg = \App\Support\WhatsAppMessage::accountCreated(
            $user->name,
            $user->phone,
            $password,
            url('/login'),
            $note
        );

        try{
            $this->wpMessage($user->phone, $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function sendWhatsappMsgForVendorAccountToAdmin($user, $password){

        $msg = \App\Support\WhatsAppMessage::vendorAccountAdminNotice($user->name, $user->phone, $password);

        try{
            $this->wpMessage(getenv('ADMIN_NUMBER'), $msg);
        }
        catch(\Exception $e){

        }

        return true;
    }

    public function stockDurationSave($id, $qty) {
        if (! \Illuminate\Support\Facades\Schema::hasTable('stock_durations')) {
            return;
        }

        $stockDuration = StockDuration::where([
            'product_id' => $id,
            'restock' => null
        ])->first();
        if ($qty == 0.0) {
            if(!$stockDuration) {
                StockDuration::create([
                    'product_id' => $id,
                    'out_of_stock' => date('Y-m-d')
                ]);
            }
        } else {
            if ($stockDuration) {
                $stockDuration->update(['restock' => date('Y-m-d')]);
            }
        }
    }

}
