<?php

namespace App\Services;

use App\ContactMessage;
use App\Services\Messaging\NotificationRouter;
use App\Support\MessageSerial;
use App\Support\SiteBrand;
use App\Support\VisitorLocale;
use App\Support\WhatsAppMessage;
use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class ContactInquiryService
{
    public function submit(array $input, $ip = null)
    {
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $subject = trim((string) ($input['subject'] ?? 'Website enquiry'));
        $body = trim((string) ($input['message'] ?? ''));
        $serial = MessageSerial::next('MSG');
        $locale = VisitorLocale::current();

        if (Schema::hasTable('contact_messages')) {
            try {
                ContactMessage::create(\App\Support\SchemaColumns::forTable('contact_messages', [
                    'serial' => $serial,
                    'name' => $name ?: 'Guest',
                    'email' => $email !== '' ? $email : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'subject' => $subject,
                    'message' => $body,
                    'ip' => $ip,
                    'preferred_locale' => $locale,
                ]));
            } catch (\Throwable $e) {
                Log::warning('[contact] save failed: '.$e->getMessage());
            }
        }

        $staffMsg = WhatsAppMessage::contactWebsiteMessage($name, $phone, $email, $subject, $body, $serial);
        $this->notifyStaffWhatsApp($staffMsg);
        $this->notifyStaffEmail($serial, $name, $email, $phone, $subject, $body);

        if ($phone !== '') {
            try {
                app(NotificationRouter::class)->sendWhatsAppText(
                    $phone,
                    WhatsAppMessage::withLocale($locale, function () use ($name, $subject, $serial) {
                        return WhatsAppMessage::contactVisitorAck($name, $subject, $serial);
                    }),
                    ['title' => 'Message received']
                );
            } catch (\Throwable $e) {
                Log::info('[contact] visitor WhatsApp skipped: '.$e->getMessage());
            }
        }

        return [
            'serial' => $serial,
            'ok' => true,
        ];
    }

    protected function notifyStaffWhatsApp($message)
    {
        $router = app(NotificationRouter::class);
        $sent = [];

        $club = SiteBrand::phone();
        if ($club !== '') {
            $sent[] = $club;
            try {
                $router->sendWhatsAppText($club, $message, ['title' => 'New contact message']);
            } catch (\Throwable $e) {
                Log::warning('[contact] club WhatsApp failed: '.$e->getMessage());
            }
        }

        try {
            $admins = User::where('is_deleted', false)->where('is_active', 1)->where('role_id', '<=', 2)
                ->whereNotNull('phone')->where('phone', '!=', '')->get();
            foreach ($admins as $admin) {
                $phone = trim((string) $admin->phone);
                if ($phone === '' || in_array($phone, $sent, true)) {
                    continue;
                }
                $sent[] = $phone;
                $router->sendWhatsAppText($phone, $message, ['title' => 'New contact message']);
            }
        } catch (\Throwable $e) {
            Log::warning('[contact] admin WhatsApp failed: '.$e->getMessage());
        }
    }

    protected function notifyStaffEmail($serial, $name, $email, $phone, $subject, $body)
    {
        $to = SiteBrand::email();
        if ($to === '') {
            return;
        }

        try {
            Mail::send('mail.contact_message', [
                'company' => WhatsAppMessage::companyName(),
                'serial' => $serial,
                'subject' => $subject,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'body' => $body,
                'sent_at' => now()->format('d M Y H:i'),
                'locale' => 'en',
            ], function ($message) use ($to, $subject, $serial, $email) {
                $message->to($to)->subject(WhatsAppMessage::emailSubject($subject, $serial));
                if ($email !== '') {
                    $message->replyTo($email);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('[contact] email failed: '.$e->getMessage());
        }
    }
}
