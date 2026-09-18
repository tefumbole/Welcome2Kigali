<?php

namespace App\Console\Commands;

use App\BookingProduct;
use App\GeneralSetting;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RentalReturnReminderCron extends Command
{
    protected $signature = 'rental:return-reminders';

    protected $description = 'Send WhatsApp reminders 5 hours before rental return and late-return penalty notices';

    public function handle()
    {
        $controller = new Controller();
        $generalSetting = GeneralSetting::first();
        $company = $generalSetting->site_title ?? 'Our Company';
        $now = Carbon::now();

        $upcomingLines = BookingProduct::with(['booking.customer', 'product'])
            ->where('is_return', false)
            ->whereNull('return_reminder_sent_at')
            ->whereNotNull('end')
            ->whereBetween('end', [$now->copy()->addHours(4)->addMinutes(50), $now->copy()->addHours(5)->addMinutes(10)])
            ->get();

        foreach ($upcomingLines as $line) {
            $customer = optional($line->booking)->customer;
            if (!$customer || empty($customer->phone_number)) {
                continue;
            }

            $productName = optional($line->product)->name ?? 'Equipment';
            $returnAt = Carbon::parse($line->end)->format('d M Y, H:i');

            $msg = \App\Support\WhatsAppMessage::rentalReturnReminder(
                $customer->name,
                $productName,
                $returnAt,
                optional($line->booking)->reference_no
            );

            try {
                $controller->wpMessage($customer->phone_number, $msg);
                $line->update(['return_reminder_sent_at' => $now]);
            } catch (\Exception $e) {
                $this->error('Reminder failed for booking product #' . $line->id . ': ' . $e->getMessage());
            }
        }

        // Only recently overdue lines (last 36h). Older backlog must never spam clients.
        $lateCutoff = $now->copy()->subHours(36);
        $lateLines = BookingProduct::with(['booking.customer', 'product'])
            ->where('is_return', false)
            ->whereNull('late_notice_sent_at')
            ->whereNotNull('end')
            ->where('end', '<', $now)
            ->where('end', '>=', $lateCutoff)
            ->get();

        foreach ($lateLines as $line) {
            $customer = optional($line->booking)->customer;
            if (!$customer || empty($customer->phone_number)) {
                $line->update(['late_notice_sent_at' => $now]);
                continue;
            }

            $productName = optional($line->product)->name ?? 'Equipment';
            $returnAt = Carbon::parse($line->end)->format('d M Y, H:i');
            $dailyRate = number_format((float) $line->net_unit_price, 2);

            $msg = \App\Support\WhatsAppMessage::lateReturnNotice(
                $customer->name,
                $company,
                $productName,
                $returnAt,
                optional($line->booking)->reference_no,
                $dailyRate
            );

            try {
                $controller->wpMessage($customer->phone_number, $msg);
            } catch (\Exception $e) {
                $this->error('Late notice failed for booking product #' . $line->id . ': ' . $e->getMessage());
            }
            // Always stamp so a failed/undelivered send cannot retry forever.
            $line->update(['late_notice_sent_at' => $now]);
        }

        // Suppress ancient overdue lines that would otherwise retry forever.
        $suppressed = BookingProduct::where('is_return', false)
            ->whereNull('late_notice_sent_at')
            ->whereNotNull('end')
            ->where('end', '<', $lateCutoff)
            ->update(['late_notice_sent_at' => $now]);
        if ($suppressed > 0) {
            $this->info("Suppressed {$suppressed} stale late-notice candidate(s).");
        }

        $this->info('Rental return reminders processed.');
        return 0;
    }
}
