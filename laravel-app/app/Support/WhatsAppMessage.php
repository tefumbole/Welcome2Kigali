<?php

namespace App\Support;

use App\GeneralSetting;

class WhatsAppMessage
{
    /** @var string Serial allocated for the current message (shown in header + footer). */
    protected static $lastSerial = '';

    /** @var string|null Locale for spoken copy (en|fr|rw). Null = visitor/request locale. */
    protected static $locale = null;

    public static function locale($locale = null)
    {
        if ($locale !== null) {
            self::$locale = VisitorLocale::normalize($locale);
        }

        return VisitorLocale::normalize(self::$locale ?: VisitorLocale::current());
    }

    public static function withLocale($locale, callable $callback)
    {
        $previous = self::$locale;
        self::$locale = VisitorLocale::normalize($locale);
        try {
            return $callback();
        } finally {
            self::$locale = $previous;
        }
    }

    public static function t($key, $replace = [])
    {
        return trans('whatsapp.'.$key, $replace, self::locale());
    }

    public static function choice($key, $number, $replace = [])
    {
        return trans_choice('whatsapp.'.$key, $number, $replace, self::locale());
    }

    public static function companyName()
    {
        $fromEnv = trim((string) config('services.whatsapp.company_name', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $general = GeneralSetting::first();

        return $general->site_title ?? config('app.name', 'Application');
    }

    public static function lastSerial()
    {
        return self::$lastSerial;
    }

    /**
     * Mulema-style envelope: institution, serial, emoji subject, divider.
     * $serial is reused when it already looks institutional (e.g. W2K/ANN/26/0000001).
     */
    public static function statusBlock($emoji, $title, $serial = null)
    {
        $company = self::companyName();
        $emoji = trim((string) $emoji);
        $title = trim((string) $title);
        $serial = self::resolveSerial($serial, $title);
        self::$lastSerial = $serial;

        $heading = $emoji !== '' ? "{$emoji} *{$title}*" : "*{$title}*";

        return "*{$company}*\n"
            ."{$serial}\n\n"
            ."{$heading}\n"
            ."──────────────\n\n";
    }

    public static function resolveSerial($serial, $title = 'Official Message')
    {
        $serial = trim((string) $serial);
        if (self::looksLikeSerial($serial)) {
            return $serial;
        }

        return MessageSerial::next(self::typeFromTitle($title));
    }

    public static function looksLikeSerial($value)
    {
        $value = trim((string) $value);

        return (bool) preg_match('/\A[A-Z0-9]{2,8}(?:\/[A-Z0-9._-]{1,12})*\/\d{2}\/\d{4,}\z/i', $value);
    }

    public static function typeFromTitle($title)
    {
        $t = strtolower((string) $title);
        $map = [
            'otp' => 'OTP', 'auth' => 'OTP', 'verif' => 'OTP', 'code' => 'OTP',
            'quot' => 'QTE',
            'book' => 'BKG', 'rental' => 'BKG',
            'sale' => 'SAL', 'order' => 'SAL', 'pos' => 'SAL',
            'contract' => 'CTR', 'agreement' => 'CTR', 'sign' => 'CTR',
            'member' => 'MBR',
            'announce' => 'ANN',
            'letter' => 'LTR',
            'intern' => 'INT',
            'job' => 'JOB', 'application' => 'JOB',
            'deliver' => 'DLV', 'goods' => 'DLV',
            'contact' => 'MSG', 'message' => 'MSG',
            'permission' => 'PRM',
            'event' => 'EVT',
            'task' => 'TSK',
            'train' => 'TRN',
            'share' => 'SHR',
        ];
        foreach ($map as $needle => $code) {
            if (strpos($t, $needle) !== false) {
                return $code;
            }
        }

        return 'MSG';
    }

    /** Wrap a raw body that is missing the institutional envelope. */
    public static function ensureEnvelope($body, $title = 'Official Message', $emoji = '✉️')
    {
        $body = str_replace(["\\r\\n", "\\n"], ["\n", "\n"], (string) $body);
        $body = trim($body);
        if ($body === '') {
            return $body;
        }
        if (preg_match('/\b[A-Z0-9]{2,8}\/[A-Z0-9._-]{1,12}\/\d{2}\/\d{4,}\b/i', $body)) {
            return $body;
        }

        if (preg_match('/^\*?Subject:\*?\s*(.+)$/mi', $body, $m)) {
            $extracted = trim($m[1]);
            if ($extracted !== '') {
                $title = $extracted;
            }
            $body = trim(preg_replace('/^\*?Subject:\*?\s*.+$/mi', '', $body, 1));
        }

        $msg = self::statusBlock($emoji, $title);
        $msg .= $body."\n";
        $msg .= self::footer();

        return $msg;
    }

    public static function emailSubject($title, $reference = null)
    {
        return MessageSerial::emailSubject($title, $reference);
    }

    public static function greeting($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return self::t('greeting')."\n\n";
        }

        return self::t('greeting_named', ['name' => $name])."\n\n";
    }

    /**
     * Shared Mulema layout: serial header, greeting in the visitor language, ☐ fields, footer.
     *
     * @param  array  $fields  label => value
     */
    public static function compose($emoji, $title, $name, $intro = '', array $fields = [], $closing = '', $serial = null)
    {
        $msg = self::statusBlock($emoji, $title, $serial);
        $msg .= self::greeting($name);
        $intro = trim((string) $intro);
        if ($intro !== '') {
            $msg .= $intro."\n\n";
        }
        foreach ($fields as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $msg .= self::bullet($label, $value);
        }
        $closing = trim((string) $closing);
        if ($closing !== '') {
            $msg .= "\n".$closing;
            if (substr($closing, -1) !== "\n") {
                $msg .= "\n";
            }
        }
        $msg .= self::footer();

        return $msg;
    }

    public static function bullet($label, $value)
    {
        $value = trim((string) $value);

        return '☐ *'.$label.':* '.($value !== '' ? $value : '—')."\n";
    }

    public static function field($label, $value)
    {
        return self::bullet($label, $value);
    }

    public static function actionLink($label, $url)
    {
        $url = AppUrl::absolute($url);
        // URL alone on its own line with no trailing punctuation. WhatsApp
        // otherwise autolinks "host.net." and nginx drops the path to Home.
        return "\n*{$label}:*\n{$url}\n";
    }

    public static function footer()
    {
        $out = "\n".self::t('regards')."\n*".self::companyName()."*";
        if (self::$lastSerial !== '') {
            $out .= "\n".self::t('serial_no').": *".self::$lastSerial."*";
        }

        return $out;
    }

    /**
     * Staff copy of a website contact form (Mulema layout, greets the club team).
     */
    public static function contactWebsiteMessage($name, $phone, $email, $subject, $body, $serial = null)
    {
        return self::withLocale('en', function () use ($name, $phone, $email, $subject, $body, $serial) {
            $serial = self::resolveSerial($serial, 'NEW CONTACT MESSAGE');
            $subjectLine = trim((string) $subject) !== '' ? trim((string) $subject) : self::t('website_enquiry');
            $msg = self::statusBlock('📩', self::t('contact_staff_title'), $serial);
            $msg .= self::greeting($name !== '' ? $name : 'Team');
            $msg .= self::t('contact_staff_intro')."\n\n";
            $msg .= self::field(self::t('label_name'), $name);
            if (trim((string) $phone) !== '') {
                $msg .= self::field(self::t('label_phone'), $phone);
            }
            $msg .= self::field(self::t('label_email'), $email);
            $msg .= self::field(self::t('label_subject'), $subjectLine);
            $body = trim((string) $body);
            $msg .= "\n☐ *".self::t('label_message').":*\n".($body !== '' ? $body : '—')."\n";
            $msg .= self::footer();

            return $msg;
        });
    }

    public static function contactStaffNotice($serial, $name, $email, $phone, $subject, $body)
    {
        return self::contactWebsiteMessage($name, $phone, $email, $subject, $body, $serial);
    }

    public static function contactVisitorAck($name, $subject, $serial)
    {
        $msg = self::statusBlock('✅', self::t('contact_visitor_title'), $serial);
        $msg .= self::greeting($name);
        $msg .= self::t('contact_visitor_intro', ['company' => self::companyName()])."\n\n";
        $msg .= self::bullet(self::t('label_subject'), $subject);
        $msg .= "\n".self::t('contact_visitor_keep');
        $msg .= self::footer();

        return $msg;
    }

    public static function rentalRequestReceived($name, $reference, $period)
    {
        $msg = self::statusBlock('📦', self::t('rental_title'), $reference);
        $msg .= self::greeting($name);
        $msg .= self::t('rental_intro')."\n\n";
        $msg .= self::bullet(self::t('label_reference'), $reference);
        $msg .= self::bullet(self::t('label_period'), $period);
        $msg .= "\n".self::t('rental_followup');
        $msg .= self::footer();

        return $msg;
    }

    public static function signatureRequest($customerName, $bookingRef, $signUrl, $company = null, $contractType = null)
    {
        $company = $company ?: self::companyName();
        if ($contractType === 'accommodation') {
            $heading = 'Accommodation Agreement';
            $body = "Please review and sign your student accommodation agreement with *{$company}*.\n\n";
        } elseif ($contractType === 'software_license') {
            $heading = 'Software License Subscription';
            $body = "Please review and sign your software license / subscription agreement with *{$company}*.\n\n";
        } elseif ($contractType === 'studio_rental') {
            $heading = 'Studio Rental Agreement';
            $body = "Please review and sign your studio rental agreement with *{$company}*.\n\n";
        } else {
            $heading = 'Rental Agreement';
            $body = "Please review and sign your equipment rental agreement with *{$company}*.\n\n";
        }

        $msg = self::statusBlock('📝', $heading);
        $msg .= self::greeting($customerName);
        $msg .= $body;
        $msg .= self::bullet('Booking Ref', $bookingRef);
        $msg .= self::actionLink('Sign agreement', $signUrl);
        $msg .= "\nYour booking receipt will be generated after admin review. You will read the agreement, sign digitally, and upload your ID card. After approval you can access your client portal.";
        $msg .= self::footer();

        return $msg;
    }

    public static function pendingReviewNotice($adminName, $customerName, $bookingRef, $reviewUrl)
    {
        $msg = self::statusBlock('⏳', 'Contract Pending Review');
        $msg .= self::greeting($adminName);
        $msg .= "*{$customerName}* has signed rental agreement *{$bookingRef}*. Please review and countersign.\n\n";
        $msg .= self::bullet('Booking Ref', $bookingRef);
        $msg .= self::bullet('Customer', $customerName);
        $msg .= self::actionLink('Review & sign', $reviewUrl);
        $msg .= self::footer();

        return $msg;
    }

    public static function contractApprovedClient($customerName, $bookingRef, $portalUrl, $username = null, $password = null)
    {
        $msg = self::statusBlock('✅', 'Contract Approved');
        $msg .= self::greeting($customerName);
        $msg .= "Your signed rental agreement for booking *{$bookingRef}* has been approved.\n\n";
        $msg .= "Signed PDF and QR code are attached.\n";
        $msg .= self::bullet('Booking Ref', $bookingRef);
        $msg .= self::actionLink('Client portal', $portalUrl);
        if ($username && $password) {
            $msg .= self::bullet('Username', $username);
            $msg .= self::bullet('Password', $password);
        }
        $msg .= "\nScan the QR code to view rented equipment and return dates.";
        $msg .= self::footer();

        return $msg;
    }

    public static function contractApprovedStaff($staffName, $customerName, $bookingRef, $scanUrl)
    {
        $msg = self::statusBlock('✅', 'Contract Finalized');
        $msg .= self::greeting($staffName);
        $msg .= "Rental agreement *{$bookingRef}* for *{$customerName}* is fully signed.\n\n";
        $msg .= self::bullet('Booking Ref', $bookingRef);
        $msg .= self::bullet('Customer', $customerName);
        $msg .= self::actionLink('QR scan page', $scanUrl);
        $msg .= "\nSigned PDF copy is attached.";
        $msg .= self::footer();

        return $msg;
    }

    public static function awaitingSignatureNotice($staffName, $customerName, $bookingRef, $awaitingUrl)
    {
        $msg = self::statusBlock('📨', 'Awaiting Client Signature');
        $msg .= self::greeting($staffName);
        $msg .= "Rental agreement *{$bookingRef}* is waiting for *{$customerName}* to sign.\n\n";
        $msg .= self::bullet('Booking Ref', $bookingRef);
        $msg .= self::bullet('Customer', $customerName);
        $msg .= self::actionLink('View awaiting list', $awaitingUrl);
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Client-facing quotation WhatsApp.
     *
     * Never lists undiscounted line prices when a discount applies — clients see
     * subtotal / discount / final total (or final total only).
     *
     * @param  array  $options  products [['name','qty']], subtotal, order_discount,
     *                          order_tax, shipping_cost, show_discount (bool)
     */
    public static function quotationApprovalRequest($customerName, $referenceNo, $grandTotal, $approvalUrl, array $options = [])
    {
        $msg = self::statusBlock('📋', 'Quotation for Signature');
        $msg .= self::greeting($customerName);
        $msg .= "Please review and sign your quotation from *".self::companyName()."*.\n\n";
        $msg .= self::bullet('Reference', $referenceNo);
        $msg .= self::quotationProductsBlock($options['products'] ?? []);
        $msg .= self::quotationPricingBlock($grandTotal, $options);
        $msg .= "\nThis is a *quotation* (not a receipt). Open the link to *Sign & Approve*, *Reject*, or *Quote* (propose your own amounts).\n";
        $msg .= "*The official PDF quotation will be sent to you only after you sign.*\n";
        $msg .= self::actionLink('Review quotation', $approvalUrl);
        $msg .= self::footer();

        return $msg;
    }

    public static function quotationClientQuoteSubmitted(
        $recipientName,
        $referenceNo,
        $customerName,
        $originalTotal,
        $proposedTotal,
        $mode,
        $reviewUrl = null,
        $clientNote = ''
    ) {
        $modeLabel = $mode === 'lines' ? 'Item prices' : 'Overall total';
        $msg = self::statusBlock('💬', 'Client Quote Submitted');
        $msg .= self::greeting($recipientName);
        $msg .= "*{$customerName}* submitted a quote on quotation *{$referenceNo}*.\n\n";
        $msg .= self::bullet('Reference', $referenceNo);
        $msg .= self::bullet('Mode', $modeLabel);
        $msg .= self::bullet('Original total', number_format((float) $originalTotal, 2));
        $msg .= self::bullet('Proposed total', number_format((float) $proposedTotal, 2));
        if ($clientNote !== '' && $clientNote !== null) {
            $msg .= "\n*Client note:*\n{$clientNote}\n";
        }
        if ($reviewUrl) {
            $msg .= self::actionLink('Review client quote', $reviewUrl);
        }
        $msg .= self::footer();

        return $msg;
    }

    public static function quotationSignedPdf($customerName, $referenceNo, $grandTotal)
    {
        $msg = self::statusBlock('✅', 'Signed Quotation');
        $msg .= self::greeting($customerName);
        $msg .= "Thank you for signing. Please find your official quotation PDF from *".self::companyName()."*.\n\n";
        $msg .= self::bullet('Reference', $referenceNo);
        $msg .= self::bullet('Total', number_format((float) $grandTotal, 2));
        $msg .= self::footer();

        return $msg;
    }

    public static function quotationNoSignaturePdf($customerName, $referenceNo, $grandTotal)
    {
        $msg = self::statusBlock('📄', 'Quotation');
        $msg .= self::greeting($customerName);
        $msg .= "Please find your quotation PDF from *".self::companyName()."*.\n\n";
        $msg .= self::bullet('Reference', $referenceNo);
        $msg .= self::bullet('Total', number_format((float) $grandTotal, 2));
        $msg .= self::footer();

        return $msg;
    }

    public static function deliverySignatureRequest($customerName, $deliveryRef, $saleRef, $signUrl)
    {
        $msg = self::statusBlock('📦', 'Confirm Delivery Receipt');
        $msg .= self::greeting($customerName);
        $msg .= "Please confirm that you have received your goods from *".self::companyName()."*.\n\n";
        $msg .= self::bullet('Delivery Ref', $deliveryRef);
        $msg .= self::bullet('Sale Ref', $saleRef);
        $msg .= "\nOpen the link, review the items, and sign digitally. The link expires after you sign.\n";
        $msg .= self::actionLink('Sign delivery receipt', $signUrl);
        $msg .= self::footer();

        return $msg;
    }

    public static function deliverySignedDocument($customerName, $deliveryRef, $saleRef)
    {
        $msg = self::statusBlock('✅', 'Signed Delivery');
        $msg .= self::greeting($customerName);
        $msg .= "Please find your signed delivery note from *".self::companyName()."*.\n\n";
        $msg .= self::bullet('Delivery Ref', $deliveryRef);
        $msg .= self::bullet('Sale Ref', $saleRef);
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Notify quotation creator / CC on send, approve, or reject.
     *
     * @param  string  $event  sent|approved|rejected|quoted|quote_accepted|quote_rejected
     */
    public static function quotationStakeholderNotify(
        $recipientName,
        $event,
        $referenceNo,
        $customerName,
        $grandTotal,
        $comment = '',
        array $lines = [],
        $approvalUrl = null,
        $listUrl = null,
        array $pricing = []
    ) {
        $event = strtolower((string) $event);
        if ($event === 'approved') {
            $msg = self::statusBlock('✅', 'Quotation Approved');
            $msg .= self::greeting($recipientName);
            $msg .= "*{$customerName}* approved quotation *{$referenceNo}*.\n\n";
        } elseif ($event === 'rejected') {
            $msg = self::statusBlock('❌', 'Quotation Rejected');
            $msg .= self::greeting($recipientName);
            $msg .= "*{$customerName}* rejected quotation *{$referenceNo}*.\n\n";
        } elseif ($event === 'quoted') {
            $msg = self::statusBlock('💬', 'Client Quote');
            $msg .= self::greeting($recipientName);
            $msg .= "*{$customerName}* submitted a quote on quotation *{$referenceNo}*.\n\n";
        } elseif ($event === 'quote_accepted') {
            $msg = self::statusBlock('✅', 'Quote Accepted');
            $msg .= self::greeting($recipientName);
            $msg .= "Client quote on *{$referenceNo}* was accepted. Updated quotation sent for signature.\n\n";
        } elseif ($event === 'quote_rejected') {
            $msg = self::statusBlock('❌', 'Quote Rejected');
            $msg .= self::greeting($recipientName);
            $msg .= "Client quote on *{$referenceNo}* was rejected. Original amounts kept.\n\n";
        } else {
            $msg = self::statusBlock('📤', 'Quotation Sent for Approval');
            $msg .= self::greeting($recipientName);
            $msg .= "Quotation *{$referenceNo}* was sent to *{$customerName}* for approval.\n\n";
        }

        $msg .= self::bullet('Reference', $referenceNo);
        $msg .= self::bullet('Client', $customerName);
        $msg .= self::quotationProductsBlock($lines);
        $msg .= self::quotationPricingBlock($grandTotal, $pricing);

        if ($comment !== '' && $comment !== null) {
            $msg .= "\n*Client comment:*\n{$comment}\n";
        }

        if (in_array($event, ['sent', 'quote_accepted'], true) && $approvalUrl) {
            $msg .= self::actionLink('Client approval link', $approvalUrl);
        }
        if ($listUrl) {
            $msg .= self::actionLink('Open quotations', $listUrl);
        }
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Product lines as name × qty only (no misleading pre-discount amounts).
     *
     * @param  array  $products  [['name'=>,'qty'=>], ...]
     */
    public static function quotationProductsBlock(array $products)
    {
        if (empty($products)) {
            return '';
        }

        $msg = "\n*Items:*\n";
        foreach ($products as $index => $line) {
            $name = $line['name'] ?? (is_string($line) ? $line : 'Item');
            $qty = $line['qty'] ?? '';
            $msg .= ($index + 1).") {$name}";
            if ($qty !== '' && $qty !== null) {
                $msg .= " × {$qty}";
            }
            $msg .= "\n";
        }

        return $msg;
    }

    /**
     * Final pricing for clients: optional subtotal + discount, then total due.
     *
     * @param  string|float  $grandTotal  already formatted or numeric
     */
    public static function quotationPricingBlock($grandTotal, array $options = [])
    {
        $subtotal = (float) ($options['subtotal'] ?? 0);
        $discount = (float) ($options['order_discount'] ?? 0);
        $tax = (float) ($options['order_tax'] ?? 0);
        $shipping = (float) ($options['shipping_cost'] ?? 0);
        $showDiscount = array_key_exists('show_discount', $options)
            ? (bool) $options['show_discount']
            : ($discount > 0);
        $formattedGrand = is_numeric($grandTotal)
            ? number_format((float) $grandTotal, 2)
            : (string) $grandTotal;

        $msg = "\n*Amount:*\n";
        if ($subtotal > 0) {
            $msg .= self::bullet('Subtotal', number_format($subtotal, 2));
        }
        if ($showDiscount && $discount > 0) {
            $msg .= self::bullet('Discount', '-'.number_format($discount, 2));
        }
        if ($tax > 0) {
            $msg .= self::bullet('Tax', number_format($tax, 2));
        }
        if ($shipping > 0) {
            $msg .= self::bullet('Shipping', number_format($shipping, 2));
        }
        $msg .= self::bullet('Total due', $formattedGrand);

        return $msg;
    }

    public static function bookingConfirmation($customerName, $referenceNo, $orderDate, array $lines, $grandTotal, $payingMethod, $facilityName, $facilityAddress, $facilityPhone, $bookingNote = '')
    {
        $msg = self::statusBlock('✅', 'Booking Confirmed');
        $msg .= self::greeting($customerName);
        $msg .= self::bullet('Order Number', $referenceNo);
        $msg .= self::bullet('Order Date', $orderDate);
        $msg .= "\n*Products:*\n";

        foreach ($lines as $index => $line) {
            $msg .= ($index + 1) . ") {$line['name']} × {$line['qty']} = {$line['total']}\n";
            $msg .= "   Start: {$line['start']}\n";
            $msg .= "   End: {$line['end']}\n";
        }

        if ($bookingNote !== '') {
            $msg .= "\n*Special Requests:*\n{$bookingNote}\n";
        }

        $msg .= "\n*Facility:*\n";
        $msg .= self::bullet('Name', $facilityName);
        $msg .= self::bullet('Address', $facilityAddress);
        $msg .= self::bullet('Contact', $facilityPhone);
        $msg .= "\n*Payment:*\n";
        $msg .= self::bullet('Total', $grandTotal);
        $msg .= self::bullet('Method', $payingMethod);
        $msg .= "\nThank you for choosing *" . self::companyName() . '*.';
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Client-facing sale confirmation (POS / sales). Same visual language as OTP:
     * heading block, bold labels, short bullets — real newlines (not literal \n).
     *
     * @param  array  $lines  [['name'=>,'qty'=>,'unit_price'=>,'total'=>], ...]
     */
    public static function saleConfirmation(
        $customerName,
        $referenceNo,
        $orderDate,
        array $lines,
        $grandTotal,
        $payingMethod,
        $billerName = '',
        $billingAddress = '',
        $deliveryAddress = '',
        $currencyCode = ''
    ) {
        $company = self::companyName();
        $money = function ($amount) use ($currencyCode) {
            $formatted = is_numeric($amount)
                ? number_format((float) $amount, 2)
                : (string) $amount;
            return $currencyCode !== '' ? trim($currencyCode.' '.$formatted) : $formatted;
        };

        $msg = self::statusBlock('🧾', self::t('sale_title'));
        $msg .= self::greeting($customerName);
        $msg .= self::t('sale_intro', ['company' => $company])."\n\n";
        $msg .= self::bullet(self::t('sale_order_no'), $referenceNo);
        $msg .= self::bullet(self::t('sale_order_date'), $orderDate);

        if (! empty($lines)) {
            $msg .= "\n*".self::t('sale_items').":*\n";
            foreach ($lines as $index => $line) {
                $name = $line['name'] ?? 'Item';
                $qty = $line['qty'] ?? '';
                $unit = isset($line['unit_price']) ? $money($line['unit_price']) : null;
                $total = isset($line['total']) ? $money($line['total']) : '';
                $msg .= ($index + 1).") *{$name}*";
                if ($qty !== '' && $qty !== null) {
                    $msg .= " × {$qty}";
                }
                if ($unit !== null) {
                    $msg .= " @ {$unit}";
                }
                if ($total !== '') {
                    $msg .= " = *{$total}*";
                }
                $msg .= "\n";
            }
        }

        $msg .= "\n";
        $msg .= self::bullet(self::t('sale_total'), $money($grandTotal));
        $msg .= self::bullet(self::t('sale_payment'), $payingMethod ?: '—');
        if (trim((string) $billingAddress) !== '') {
            $msg .= self::bullet(self::t('sale_billing'), $billingAddress);
        }
        if (trim((string) $deliveryAddress) !== '') {
            $msg .= self::bullet(self::t('sale_delivery'), $deliveryAddress);
        }
        if (trim((string) $billerName) !== '') {
            $msg .= self::bullet(self::t('sale_served'), $billerName);
        }

        $msg .= "\n".self::t('sale_thanks', ['company' => $company]);
        $msg .= self::footer();

        return $msg;
    }

    public static function lateReturnNotice($customerName, $company, $productName, $returnAt, $bookingRef, $dailyRate)
    {
        $msg = self::statusBlock('⚠️', 'Late Equipment Return');
        $msg .= self::greeting($customerName);
        $msg .= "Our records show rented equipment from *{$company}* was not returned by the agreed date.\n\n";
        $msg .= self::bullet('Equipment', $productName);
        $msg .= self::bullet('Required return', $returnAt);
        $msg .= self::bullet('Booking Ref', $bookingRef);
        $msg .= "\nPer your signed agreement, late return incurs an additional full-day charge (approx. {$dailyRate}) per day or part thereof, plus repair/replacement costs for damage.\n\n";
        $msg .= 'Please return the equipment immediately or contact us to resolve this matter.';
        $msg .= self::footer();

        return $msg;
    }

    public static function otpPurposeLabel($purpose = null)
    {
        $key = strtolower(trim((string) $purpose));
        $map = [
            'login' => 'otp_login',
            'password_reset' => 'otp_password_reset',
            'password reset' => 'otp_password_reset',
            'reset' => 'otp_password_reset',
            'register' => 'otp_register',
            'verify' => 'otp_verify',
            'permission' => 'otp_permission',
            'membership' => 'otp_membership',
        ];
        if (isset($map[$key])) {
            return self::t($map[$key]);
        }

        return $purpose ? ucwords(str_replace('_', ' ', (string) $purpose)) : self::t('otp_login');
    }

    /**
     * Standard OTP / authentication WhatsApp template.
     * Heading is always "Authentication".
     */
    public static function otpMessage($otp, $purpose = 'login', $expiresMinutes = 10)
    {
        $company = self::companyName();
        $purposeLabel = self::otpPurposeLabel($purpose);
        $minutes = max(1, (int) $expiresMinutes);
        $otp = preg_replace('/\D/', '', (string) $otp);

        $msg = self::statusBlock('🔐', self::t('otp_title'));
        $msg .= self::greeting('');
        $msg .= self::t('otp_thanks', ['company' => $company])."\n\n";
        $msg .= self::t('otp_intro', ['purpose' => $purposeLabel])."\n\n";
        $msg .= "*{$otp}*\n\n";
        $msg .= self::choice('otp_valid', $minutes, ['minutes' => $minutes])."\n\n";
        $msg .= self::t('otp_share');
        $msg .= self::footer();

        return $msg;
    }

    public static function accountCreated($name, $phone, $password, $loginUrl = null, $note = null)
    {
        $msg = self::statusBlock('🎉', 'Account Created');
        $msg .= self::greeting($name);
        $msg .= "Your account on *" . self::companyName() . "* has been created.\n\n";
        $msg .= self::bullet('Name', $name);
        $msg .= self::bullet('Phone', $phone);
        $msg .= self::bullet('Password', $password);
        if ($loginUrl) {
            $msg .= self::actionLink('Sign in', $loginUrl);
        }
        if ($note) {
            $msg .= "\n*Note:* {$note}\n";
        }
        $msg .= "\nPlease change your password after first login.";
        $msg .= self::footer();

        return $msg;
    }

    public static function applicationUnderReview($name, $jobTitle, $reference, $isInternship = false)
    {
        $kind = $isInternship ? 'Internship' : 'Job';
        $msg = self::statusBlock('📩', $kind.' Application');
        $msg .= self::greeting($name);
        $msg .= "Your application for *{$jobTitle}* has been received and is now *under review*.\n\n";
        $msg .= self::bullet('Reference', $reference);
        $msg .= self::bullet('Type', $kind);
        $msg .= "\nWe will notify you on WhatsApp at every stage. Please keep this number available.";
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Admin alert when a new application is submitted (same style as applicant receipt).
     */
    public static function applicationUnderReviewAdmin($adminName, $applicantName, $jobTitle, $reference, $loginUrl, $isInternship = false, $applicantPhone = null)
    {
        $kind = $isInternship ? 'Internship' : 'Job';
        $msg = self::statusBlock('📩', $kind.' Application');
        $msg .= self::greeting($adminName ?: 'Admin');
        $msg .= "A new application for *{$jobTitle}* has been received and is now *under review*.\n\n";
        $msg .= self::bullet('Applicant', $applicantName ?: '—');
        if ($applicantPhone) {
            $msg .= self::bullet('WhatsApp', $applicantPhone);
        }
        $msg .= self::bullet('Reference', $reference);
        $msg .= self::bullet('Type', $kind);
        $msg .= self::actionLink('Login to review application', $loginUrl);
        $msg .= "\nTap the link, sign in, and you will open this application directly.";
        $msg .= self::footer();

        return $msg;
    }

    public static function applicationSelected($name, $jobTitle, $reference, $agreementUrl, $isInternship = false, $offerPortal = false)
    {
        $kind = $isInternship ? 'Internship' : 'Employment';
        $msg = self::statusBlock('✅', 'Selected');
        $msg .= self::greeting($name);
        $msg .= "Congratulations! You have been *selected* for the {$kind} role *{$jobTitle}*.\n\n";
        $msg .= self::bullet('Reference', $reference);
        $linkLabel = ($isInternship && $offerPortal)
            ? 'Accept your offer & set up your account'
            : 'Sign your agreement';
        $msg .= self::actionLink($linkLabel, $agreementUrl);
        $msg .= $offerPortal
            ? "\nUse the link to accept the offer, create your password, and confirm your Working Week."
            : "\nAfter signing, you will receive a WhatsApp confirmation.";
        $msg .= self::footer();

        return $msg;
    }

    public static function applicationRejected($name, $jobTitle, $reference, $reason = null)
    {
        $msg = self::statusBlock('❌', 'Application Update');
        $msg .= self::greeting($name);
        $msg .= "Thank you for applying for *{$jobTitle}* at *" . self::companyName() . "*.\n\n";
        $msg .= self::bullet('Reference', $reference);
        $msg .= "\nAfter careful review, we are unable to proceed with your application at this time.\n";
        if ($reason) {
            $msg .= self::bullet('Reason', $reason);
        }
        $msg .= "\nWe wish you the best in your future opportunities.";
        $msg .= self::footer();

        return $msg;
    }

    public static function applicationDocumentsUpdateRequested($name, $jobTitle, $reference, $updateUrl, array $missingLabels = [], $note = null)
    {
        $msg = self::statusBlock('📎', 'Documents Needed');
        $msg .= self::greeting($name);
        $msg .= "Please upload the missing documents for your application to *{$jobTitle}*.\n\n";
        $msg .= self::bullet('Reference', $reference);
        if (! empty($missingLabels)) {
            $msg .= self::bullet('Missing', implode(', ', $missingLabels));
        }
        if ($note) {
            $msg .= self::bullet('Note', $note);
        }
        $msg .= self::actionLink('Upload documents', $updateUrl);
        $msg .= "\nYou can open the link on your phone, snap photos, and submit.";
        $msg .= self::footer();

        return $msg;
    }

    public static function applicationAgreementSigned($name, $jobTitle, $reference, $isInternship = false)
    {
        $kind = $isInternship ? 'Internship' : 'Employment';
        $msg = self::statusBlock('📝', $kind.' Agreement Signed');
        $msg .= self::greeting($name);
        $msg .= "Your {$kind} agreement for *{$jobTitle}* has been signed and received.\n\n";
        $msg .= self::bullet('Reference', $reference);
        $msg .= self::bullet('Working hours', $isInternship ? 'Per your Working Week' : '7:30 AM – 4:00 PM');
        $msg .= self::bullet('Timesheets', $isInternship ? 'Daily on your working days' : 'Daily · minimum 40 hours per week');
        $msg .= "\nFailure to complete assigned tasks may result in termination.\n\n";
        $msg .= 'Welcome to *' . self::companyName() . '*.';
        $msg .= self::footer();

        return $msg;
    }

    public static function shareholderRegistration($name, $reference, $shares, $investmentLabel, $verifyUrl)
    {
        $msg = self::statusBlock('📈', 'Shareholder Registration');
        $msg .= self::greeting($name);
        $msg .= "Your shareholder registration with *" . self::companyName() . "* has been received.\n\n";
        $msg .= self::bullet('Reference', $reference);
        $msg .= self::bullet('Shares', $shares);
        $msg .= self::bullet('Investment', $investmentLabel);
        $msg .= "\nOur team will contact you with payment instructions.";
        $msg .= self::actionLink('Verify signed agreement', $verifyUrl);
        $msg .= self::footer();

        return $msg;
    }

    public static function trainingRegistration($name, $reference, $courses)
    {
        $msg = self::statusBlock('🎓', 'Training Registration');
        $msg .= self::greeting($name);
        $msg .= "Your training registration with *" . self::companyName() . "* has been received.\n\n";
        $msg .= self::bullet('Reference', $reference);
        $msg .= self::bullet('Courses', $courses);
        $msg .= "\nOur team will contact you shortly with the next steps.";
        $msg .= self::footer();

        return $msg;
    }

    public static function eventContractSignRequest($workerName, $eventName, $signUrl)
    {
        $msg = self::statusBlock('📝', 'Event Contract');
        $msg .= self::greeting($workerName ?: 'Team member');
        $msg .= "Please review and sign your event contract with *" . self::companyName() . "*.\n\n";
        $msg .= self::bullet('Event', $eventName);
        $msg .= self::actionLink('Sign contract', $signUrl);
        $msg .= self::footer();

        return $msg;
    }

    public static function clientSignedPendingReview($customerName, $bookingRef, $reviewUrl = null)
    {
        $msg = self::statusBlock('✅', 'Agreement Signed');
        $msg .= self::greeting($customerName);
        $msg .= "Thank you for signing rental agreement *{$bookingRef}*.\n\n";
        $msg .= "Your signed contract PDF is attached. Our team will review and countersign shortly.\n";
        $msg .= self::bullet('Booking Ref', $bookingRef);
        if ($reviewUrl) {
            $msg .= self::actionLink('View status', $reviewUrl);
        }
        $msg .= self::footer();

        return $msg;
    }

    public static function bookingQuotationCc($recipientName, $bookingRef, array $lines, $customerName, $bookingNote = '')
    {
        $msg = self::statusBlock('📋', 'Quotation Copy');
        $msg .= self::greeting($recipientName);
        $msg .= "You are copied on equipment quotation *{$bookingRef}* for *{$customerName}*.\n\n";
        $msg .= "*Equipment (no pricing):*\n";

        foreach ($lines as $index => $line) {
            $msg .= ($index + 1) . ") {$line['name']} × {$line['qty']}\n";
            $msg .= "   From: {$line['start']}\n";
            $msg .= "   To: {$line['end']}\n";
        }

        if ($bookingNote !== '') {
            $plainNote = \App\Support\BookingNoteFormatter::forPlainText($bookingNote);
            if ($plainNote !== '') {
                $msg .= "\n*Notes:*\n";
                foreach (preg_split('/\r\n|\r|\n/', $plainNote) as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $msg .= "• {$line}\n";
                    }
                }
            }
        }

        $msg .= "\nThis copy excludes pricing. For full details contact the booking team.";
        $msg .= self::footer();

        return $msg;
    }

    public static function goodsReceivedSignatureRequest($customerName, $bookingRef, $deliveryRef, $signUrl, array $items = [], $role = 'received')
    {
        $isDelivered = $role === 'delivered';

        $msg = self::statusBlock('📦', 'Goods Delivery');
        $msg .= self::greeting($customerName);

        if ($isDelivered) {
            $msg .= "Please confirm you *delivered* the equipment for booking *{$bookingRef}*.\n\n";
        } else {
            $msg .= "Please confirm receipt of equipment delivered under booking *{$bookingRef}*.\n\n";
        }

        $msg .= self::bullet('Delivery Note', $deliveryRef);
        $msg .= self::bullet('Booking Ref', $bookingRef);

        if (!empty($items)) {
            $msg .= "\n*Equipment (no pricing):*\n";
            foreach ($items as $index => $item) {
                $msg .= ($index + 1) . ') ' . $item['name'] . ' × ' . $item['qty'] . "\n";
            }
        }

        if ($isDelivered) {
            $msg .= self::actionLink('Sign as delivered', $signUrl);
            $msg .= "\nReview the item list and sign to confirm you delivered the goods.";
        } else {
            $msg .= self::actionLink('Sign goods received', $signUrl);
            $msg .= "\nReview the item list and sign to confirm you received the goods.";
        }

        $msg .= self::footer();

        return $msg;
    }

    public static function goodsReceivedSignedClient($customerName, $bookingRef, $deliveryRef)
    {
        $msg = self::statusBlock('✅', 'Goods Received');
        $msg .= self::greeting($customerName);
        $msg .= "Thank you for confirming receipt of equipment for booking *{$bookingRef}*.\n\n";
        $msg .= self::bullet('Delivery Note', $deliveryRef);
        $msg .= self::bullet('Booking Ref', $bookingRef);
        $msg .= "\nSigned goods received document is attached.";
        $msg .= self::footer();

        return $msg;
    }

    public static function bookingScheduledReminder($customerName, $referenceNo, $remindAtFormatted, $customMessage = '')
    {
        $msg = self::statusBlock('🔔', 'Booking Reminder');
        $msg .= self::greeting($customerName);
        $msg .= "This is your scheduled reminder for booking *{$referenceNo}*.\n\n";
        $msg .= self::bullet('Scheduled for', $remindAtFormatted);
        if (trim($customMessage) !== '') {
            $msg .= "\n*Message:*\n{$customMessage}\n";
        }
        $msg .= "\nPlease contact us if you have any questions about your booking.";
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Notify a supervisor they have been assigned intern(s).
     *
     * @param  string  $supervisorName
     * @param  string|array  $internNames
     * @param  string  $program
     * @param  string  $startDate
     * @param  string  $durationLabel
     */
    public static function internshipSupervisorAssigned($supervisorName, $internNames, $program, $startDate, $durationLabel, $loginUrl = null)
    {
        $names = is_array($internNames) ? array_values(array_filter($internNames)) : [trim((string) $internNames)];
        $names = array_values(array_filter(array_map('strval', $names)));
        $internList = $names ? implode(', ', $names) : 'an intern';
        $loginUrl = $loginUrl ?: url('/staff-otp-login');

        $msg = self::statusBlock('🎓', 'Internship Supervision Assigned');
        $msg .= self::greeting($supervisorName ?: 'Supervisor');
        $msg .= "You have been assigned to supervise the following intern(s) under the *" . self::companyName() . "* Internship Programme.\n\n";
        $msg .= self::bullet('Intern(s)', $internList);
        $msg .= self::bullet('Program', $program ?: 'Internship Programme');
        $msg .= self::bullet('Start date', $startDate ?: '—');
        $msg .= self::bullet('Duration', $durationLabel ?: '—');
        $msg .= "\nPlease log in to the ERP to review placements, release tasks, and support your intern(s).";
        $msg .= "\n\n*Existing account:* sign in with your email/username and password.";
        $msg .= "\n*New / first-time access:* use WhatsApp OTP with your phone number, then create a password.";
        $msg .= self::actionLink('Supervisor login', $loginUrl);
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Daily internship task notice to the student, with guide checklist points.
     *
     * @param  array  $instructionSteps
     */
    public static function internshipDailyTask($studentName, $program, $taskLabel, $workDate, $url, array $instructionSteps = [], $handbookAttached = false)
    {
        $msg = self::statusBlock('📚', 'Internship Task');
        $msg .= self::greeting($studentName ?: 'Intern');
        $msg .= "Your internship task for today is ready.\n\n";
        $msg .= self::bullet('Program', $program ?: '—');
        $msg .= self::bullet('Task', $taskLabel ?: '—');
        $msg .= self::bullet('Date', $workDate ?: '—');

        $steps = array_values(array_filter(array_map(function ($line) {
            return trim(is_string($line) ? $line : json_encode($line));
        }, $instructionSteps)));
        if ($steps) {
            $msg .= "\n*Guide checklist (tick each point in the ERP):*\n";
            $max = min(count($steps), 12);
            for ($i = 0; $i < $max; $i++) {
                $label = $steps[$i];
                if (mb_strlen($label) > 120) {
                    $label = mb_substr($label, 0, 117).'…';
                }
                $msg .= ($i + 1).'. '.$label."\n";
            }
            if (count($steps) > $max) {
                $msg .= '… +'.(count($steps) - $max)." more in the dashboard\n";
            }
        }

        if ($handbookAttached) {
            $msg .= "\nA *Word handbook* for this day will be sent next on WhatsApp — you can follow it even if you cannot log in yet.\n";
        }

        $msg .= self::actionLink('Open internship dashboard', $url);
        $msg .= 'Complete each checklist item, then submit evidence from your dashboard. Only one task is released per working day.';
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Supervisor copy when an intern's daily task is released.
     */
    public static function internshipSupervisorTaskCopy($supervisorName, $studentName, $program, $taskLabel, $workDate, $dashboardUrl)
    {
        $msg = self::statusBlock('📚', 'Intern Task Released');
        $msg .= self::greeting($supervisorName ?: 'Supervisor');
        $msg .= "A daily internship task was released to your intern. A copy of the task details and the instruction handbook follow.\n\n";
        $msg .= self::bullet('Intern', $studentName ?: '—');
        $msg .= self::bullet('Program', $program ?: '—');
        $msg .= self::bullet('Task', $taskLabel ?: '—');
        $msg .= self::bullet('Date', $workDate ?: '—');
        $msg .= self::actionLink('Open supervisor portal', $dashboardUrl ?: url('/admin/internship/supervisor'));
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Nudge a supervisor whose intern is waiting for a review decision.
     */
    public static function internshipReviewReminder($supervisorName, $studentName, $taskLabel, $submittedAt, $autoAcceptAt, $url)
    {
        $msg = self::statusBlock('⏳', 'Submission Waiting');
        $msg .= self::greeting($supervisorName ?: 'Supervisor');
        $msg .= "An intern is waiting on your review before their next task can be scheduled.\n\n";
        $msg .= self::bullet('Intern', $studentName ?: '—');
        $msg .= self::bullet('Task', $taskLabel ?: '—');
        $msg .= self::bullet('Submitted', $submittedAt ?: '—');
        if ($autoAcceptAt) {
            $msg .= self::bullet('Auto-accepts', $autoAcceptAt);
            $msg .= "\nIf no decision is recorded by then, the system accepts it automatically so the placement is not delayed.\n";
        }
        $msg .= self::actionLink('Review submission', $url);
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Tell a supervisor their review window lapsed and the system accepted the work.
     */
    public static function internshipReviewSlaBreached($supervisorName, $studentName, $taskLabel, $slaDays, $nextTaskDate, $url)
    {
        $dayLabel = $slaDays.' working day'.((int) $slaDays === 1 ? '' : 's');

        $msg = self::statusBlock('⚠️', 'Auto-Accepted');
        $msg .= self::greeting($supervisorName ?: 'Supervisor');
        $msg .= "A submission passed its {$dayLabel} review window, so it was accepted automatically to keep the placement moving.\n\n";
        $msg .= self::bullet('Intern', $studentName ?: '—');
        $msg .= self::bullet('Task', $taskLabel ?: '—');
        if ($nextTaskDate) {
            $msg .= self::bullet('Next task', $nextTaskDate);
        }
        $msg .= self::actionLink('Open submission', $url);
        $msg .= 'The work was recorded at the task pass mark. Please still review it and give the intern feedback.';
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Nudge an intern who has not logged hours for a completed working day.
     */
    public static function internshipTimesheetReminder($studentName, $missingDate, $taskLabel = null, $fillUrl = null)
    {
        $fillUrl = $fillUrl ?: url('/admin/timesheet/fill');

        $msg = self::statusBlock('⏰', 'Timesheet Missing');
        $msg .= self::greeting($studentName ?: 'Intern');
        $msg .= "Your working day has ended and no hours are logged yet.\n\n";
        $msg .= self::bullet('Date', $missingDate ?: '—');
        if ($taskLabel) {
            $msg .= self::bullet('Task', $taskLabel);
        }
        $msg .= self::actionLink('Fill timesheet', $fillUrl);
        $msg .= 'Log your hours today so your supervisor can approve the day.';
        $msg .= self::footer();

        return $msg;
    }

    /**
     * Follow-up after internship admission letter PDF: login + Timesheets working week.
     */
    public static function internshipAdmissionLoginGuide($name, $username, $password, $loginUrl = null, $timesheetUrl = null)
    {
        $loginUrl = $loginUrl ?: url('/login');
        $timesheetUrl = $timesheetUrl ?: url('/admin/timesheet/working-week');

        $msg = self::statusBlock('🔑', 'Internship Login');
        $msg .= self::greeting($name ?: 'Intern');
        $msg .= "Your internship admission letter PDF was sent above. Use these details to sign in and set your Working Week.\n\n";
        $msg .= self::bullet('Username', $username ?: '—');
        $msg .= self::bullet('Default password', $password ?: 'system');
        $msg .= "_Sign in with your email, WhatsApp number, or username. If you forget either, use Forgot username / password on the login page._\n";
        $msg .= self::actionLink('Sign in', $loginUrl);
        $msg .= self::actionLink('Recover account (OTP)', url('/forgot-password'));
        $msg .= self::actionLink('Go to Timesheets → Working Week', $timesheetUrl);
        $msg .= "\nAfter login, change your password (or recover via WhatsApp OTP), then open *Timesheets* and configure your working week so daily tasks continue.";
        $msg .= self::footer();

        return $msg;
    }

    public static function membershipApplicationReceived($name, $reference)
    {
        $msg = self::statusBlock('🪪', self::t('membership_received_title'));
        $msg .= self::greeting($name);
        $msg .= self::t('membership_received_intro')."\n\n";
        $msg .= self::t('membership_received_body')."\n\n";
        $msg .= self::bullet(self::t('label_reference'), $reference);
        $msg .= self::footer();

        return $msg;
    }

    public static function membershipApplicationAdmin($adminName, $applicantName, $reference, $loginUrl)
    {
        return self::withLocale('en', function () use ($adminName, $applicantName, $reference, $loginUrl) {
            $msg = self::statusBlock('🪪', self::t('membership_admin_title'));
            $msg .= self::greeting($adminName ?: 'Team');
            $msg .= self::t('membership_admin_intro')."\n\n";
            $msg .= self::bullet(self::t('membership_admin_applicant'), $applicantName);
            $msg .= self::bullet(self::t('label_reference'), $reference);
            $msg .= self::actionLink(self::t('membership_admin_review'), $loginUrl);
            $msg .= self::footer();

            return $msg;
        });
    }

    public static function membershipApproved($name, $number, $status, $expires, $payUrl = null, $verifyUrl = null)
    {
        $company = self::companyName();
        $msg = self::statusBlock('✅', self::t('membership_update_title'));
        $msg .= self::greeting($name);
        $msg .= self::t('membership_update_intro', ['company' => $company])."\n\n";
        $msg .= self::bullet(self::t('membership_no'), $number);
        $msg .= self::bullet(self::t('membership_status'), $status);
        if ($expires) {
            $msg .= self::bullet(self::t('membership_expires'), $expires);
        }
        if ($payUrl) {
            $msg .= "\n".self::t('membership_pay_intro');
            $msg .= self::actionLink(self::t('membership_pay'), $payUrl);
        }
        if ($verifyUrl) {
            $msg .= self::actionLink(self::t('membership_card'), $verifyUrl);
        }
        $msg .= "\n".self::t('membership_welcome');
        $msg .= self::footer();

        return $msg;
    }

    public static function membershipRejected($name, $reference, $note = null)
    {
        $company = self::companyName();
        $msg = self::statusBlock('❌', self::t('membership_received_title'));
        $msg .= self::greeting($name);
        $msg .= self::t('membership_rejected_intro', ['company' => $company])."\n\n";
        $msg .= self::t('membership_rejected_body', ['reference' => $reference])."\n";
        if ($note) {
            $msg .= "\n".$note."\n";
        }
        $msg .= "\n".self::t('membership_rejected_contact');
        $msg .= self::footer();

        return $msg;
    }

    public static function membershipMoreInfo($name, $reference, $note)
    {
        $msg = self::statusBlock('📎', self::t('membership_more_title'));
        $msg .= self::greeting($name);
        $msg .= self::t('membership_more_intro', ['reference' => $reference])."\n\n";
        $msg .= self::t('membership_more_body')."\n\n";
        $msg .= $note."\n";
        $msg .= "\n".self::t('membership_more_reply');
        $msg .= self::footer();

        return $msg;
    }

    public static function membershipRenewalReminder($name, $number, $expires, $renewUrl, $kind)
    {
        $expired = ($kind === 'expired' || $kind === 'after');
        $msg = self::statusBlock('🔔', $expired ? self::t('membership_expired_title') : self::t('membership_renewal_title'));
        $msg .= self::greeting($name);
        if ($expired) {
            $msg .= self::t('membership_expired_body', ['number' => $number])."\n\n";
            $msg .= self::t('membership_expired_renew')."\n\n";
        } else {
            $msg .= self::t('membership_renewal_body', ['number' => $number])."\n\n";
        }
        $msg .= self::bullet(self::t('membership_expiry'), $expires ?: '—');
        $msg .= self::actionLink(self::t('membership_renew'), $renewUrl);
        $msg .= self::footer();

        return $msg;
    }

    public static function rentalReturnReminder($customerName, $productName, $returnAt, $bookingRef)
    {
        $msg = self::statusBlock('⏰', 'RENTAL RETURN REMINDER');
        $msg .= self::greeting($customerName);
        $msg .= "This is a reminder from *".self::companyName()."* that your rented equipment must be returned in approximately 5 hours.\n\n";
        $msg .= self::bullet('Equipment', $productName);
        $msg .= self::bullet('Return date/time', $returnAt);
        $msg .= self::bullet('Booking Ref', $bookingRef);
        $msg .= "\nPlease ensure timely return to avoid late penalties as stated in your rental agreement.";
        $msg .= self::footer();

        return $msg;
    }

    public static function eventContractApproved($workerName, $referenceNo)
    {
        return self::compose(
            '✅',
            'EVENT CONTRACT APPROVED',
            $workerName,
            'Your event contract has been approved. The signed PDF is attached.',
            ['Reference' => $referenceNo]
        );
    }

    public static function accountStatusChanged($name, $activated)
    {
        if ($activated) {
            return self::compose(
                '✅',
                'ACCOUNT ACTIVATED',
                $name,
                'Your vendor account has been activated. You can now sell products.',
                ['Account' => $name]
            );
        }

        return self::compose(
            '🚫',
            'ACCOUNT DISABLED',
            $name,
            'Your vendor account has been disabled. Please contact us if you have questions.',
            ['Account' => $name]
        );
    }

    public static function passwordUpdated($name, $password)
    {
        return self::compose(
            '🔐',
            'PASSWORD UPDATED',
            $name,
            'Your password has been updated successfully.',
            ['New password' => $password],
            'Please keep this password private.'
        );
    }

    public static function letterWorkflowNotice($recipientName, $roleName, $fromName, $subject, $action, $url, $comment = null)
    {
        $msg = self::statusBlock('✉️', 'LETTER — '.strtoupper($action));
        $msg .= self::greeting($recipientName ?: $roleName);
        $msg .= "A letter is waiting for *{$roleName}* action.\n\n";
        $msg .= self::bullet('From', $fromName);
        $msg .= self::bullet('Subject', $subject);
        $msg .= self::bullet('Action', $action);
        if ($comment) {
            $msg .= self::bullet('Comment', $comment);
        }
        $msg .= self::actionLink('Open letter', $url);
        $msg .= self::footer();

        return $msg;
    }

    public static function contractReminder($name, $contractNumber, $title, $status, $openUrl, $customMessage = '', $label = '')
    {
        $msg = self::statusBlock('⏰', 'CONTRACT REMINDER');
        $msg .= self::greeting($name);
        $intro = trim((string) $customMessage);
        $msg .= ($intro !== '' ? $intro : 'This is a reminder about your contract with *'.self::companyName().'*.')."\n\n";
        $msg .= self::bullet('Contract', $contractNumber);
        $msg .= self::bullet('Title', $title);
        $msg .= self::bullet('Status', $status);
        if ($label !== '' && $label !== null) {
            $msg .= self::bullet('Label', $label);
        }
        $msg .= self::actionLink('Open contract', $openUrl);
        $msg .= self::footer();

        return $msg;
    }

    public static function eventReminder($name, $eventName, $reference, $when, $venue, $customMessage = '')
    {
        $msg = self::statusBlock('🔔', 'EVENT REMINDER');
        $msg .= self::greeting($name);
        $intro = trim((string) $customMessage);
        $msg .= ($intro !== '' ? $intro : 'This is a scheduled reminder for your event.')."\n\n";
        $msg .= self::bullet('Event', $eventName);
        $msg .= self::bullet('Reference', $reference);
        $msg .= self::bullet('When', $when);
        $msg .= self::bullet('Venue', $venue);
        $msg .= self::footer();

        return $msg;
    }

    public static function orderStatusUpdate($customerName, $orderId, $status, $orderDate, $grandTotal, $paymentMethod, $address, $extraNote = '', array $lines = [])
    {
        $msg = self::statusBlock('🧾', self::t('order_update_title'));
        $msg .= self::greeting($customerName);
        $msg .= self::t('order_update_intro', ['status' => $status])."\n\n";
        $msg .= self::bullet(self::t('sale_order_no'), $orderId);
        $msg .= self::bullet(self::t('sale_order_date'), $orderDate);
        $msg .= self::bullet(self::t('order_status'), $status);
        if (! empty($lines)) {
            $msg .= "\n*".self::t('sale_items').":*\n";
            foreach ($lines as $index => $line) {
                $msg .= ($index + 1).') '.($line['name'] ?? 'Item');
                if (! empty($line['qty'])) {
                    $msg .= ' × '.$line['qty'];
                }
                if (isset($line['total'])) {
                    $msg .= ' = '.number_format((float) $line['total'], 2);
                }
                $msg .= "\n";
            }
        }
        $msg .= "\n";
        $msg .= self::bullet(self::t('sale_total'), number_format((float) $grandTotal, 2));
        $msg .= self::bullet(self::t('sale_payment'), $paymentMethod);
        $msg .= self::bullet(self::t('sale_delivery'), $address);
        $extraNote = trim((string) $extraNote);
        if ($extraNote !== '') {
            $msg .= "\n{$extraNote}\n";
        }
        $msg .= self::footer();

        return $msg;
    }

    public static function vendorAccountAdminNotice($vendorName, $phone, $password)
    {
        return self::compose(
            '🏪',
            'NEW VENDOR ACCOUNT',
            'Team',
            'A new vendor account is ready for review.',
            [
                'Vendor' => $vendorName,
                'Phone' => $phone,
                'Password' => $password,
            ],
            'Please review and activate this shop so the vendor can sell products.'
        );
    }

    public static function donationReceived($name, $amount, $forAdmin = false, $fromName = null)
    {
        if ($forAdmin) {
            return self::compose(
                '💛',
                'DONATION RECEIVED',
                'Team',
                'A donation has been received.',
                [
                    'From' => $fromName ?: $name,
                    'Amount' => $amount,
                ]
            );
        }

        return self::compose(
            '💛',
            'DONATION RECEIVED',
            $name,
            'Thank you for your donation to *'.self::companyName().'*.',
            ['Amount' => $amount]
        );
    }

    public static function paymentReceived($name, $amount)
    {
        return self::compose(
            '💳',
            'PAYMENT RECEIVED',
            $name,
            'Thank you. Your payment has been received.',
            ['Amount' => $amount]
        );
    }

    public static function taskAssigned($name, $taskTitle, $priority, $start, $deadline, $description, $acceptUrl)
    {
        $msg = self::statusBlock('📋', 'NEW TASK ASSIGNMENT');
        $msg .= self::greeting($name);
        $msg .= "You have been assigned a new task.\n\n";
        $msg .= self::bullet('Task', $taskTitle);
        $msg .= self::bullet('Priority', $priority ?: 'Medium');
        $msg .= self::bullet('Start', $start);
        $msg .= self::bullet('Deadline', $deadline);
        $description = trim((string) $description);
        if ($description !== '') {
            $msg .= "\n{$description}\n";
        }
        $msg .= self::actionLink('Accept or reject this task', $acceptUrl);
        $msg .= self::footer();

        return $msg;
    }

    public static function taskCcNotice($name, $assigneeNames, $taskTitle, $priority, $start, $deadline, $description)
    {
        $msg = self::statusBlock('📋', 'TASK CC NOTIFICATION');
        $msg .= self::greeting($name);
        $msg .= "You have been CC'd on a task assigned to *{$assigneeNames}*.\n\n";
        $msg .= self::bullet('Task', $taskTitle);
        $msg .= self::bullet('Priority', $priority ?: 'Medium');
        $msg .= self::bullet('Start', $start);
        $msg .= self::bullet('Deadline', $deadline);
        $description = trim((string) $description);
        if ($description !== '') {
            $msg .= "\n{$description}\n";
        }
        $msg .= self::actionLink('View tasks', url('/user/tasks'));
        $msg .= self::footer();

        return $msg;
    }

    public static function taskStatusNotice($name, $title, $emoji, $intro, array $fields = [], $link = null)
    {
        $msg = self::statusBlock($emoji, $title);
        $msg .= self::greeting($name);
        $msg .= trim((string) $intro)."\n\n";
        foreach ($fields as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $msg .= self::bullet($label, $value);
        }
        if ($link) {
            $msg .= self::actionLink('Open tasks', $link);
        }
        $msg .= self::footer();

        return $msg;
    }
}
