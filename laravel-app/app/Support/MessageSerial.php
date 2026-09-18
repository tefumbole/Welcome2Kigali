<?php

namespace App\Support;

use App\GeneralSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Institutional serials so every outbound message can be traced
 * (e.g. W2K/MSG/26/0000042).
 */
class MessageSerial
{
    public static function institutionPrefix(): string
    {
        $setting = GeneralSetting::first();
        $fromLetters = trim((string) optional($setting)->letter_serial_no);
        if ($fromLetters !== '' && preg_match('/^W2K/i', $fromLetters)) {
            return 'W2K';
        }

        return 'W2K';
    }

    public static function next($type = 'MSG'): string
    {
        $type = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $type) ?: 'MSG');
        if (strlen($type) > 6) {
            $type = substr($type, 0, 6);
        }
        $year = date('y');
        $prefix = self::institutionPrefix();

        if (! Schema::hasTable('institutional_message_counters')) {
            return $prefix.'/'.$type.'/'.$year.'/'.str_pad((string) time(), 7, '0', STR_PAD_LEFT);
        }

        return DB::transaction(function () use ($type, $year, $prefix) {
            $row = DB::table('institutional_message_counters')
                ->where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                DB::table('institutional_message_counters')->insert([
                    'type' => $type,
                    'year' => $year,
                    'next_number' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $n = 1;
            } else {
                $n = max(1, (int) $row->next_number);
                DB::table('institutional_message_counters')
                    ->where('id', $row->id)
                    ->update([
                        'next_number' => $n + 1,
                        'updated_at' => now(),
                    ]);
            }

            return $prefix.'/'.$type.'/'.$year.'/'.str_pad((string) $n, 7, '0', STR_PAD_LEFT);
        });
    }

    /** Alias used by WhatsApp envelopes. */
    public static function allocate($type = 'MSG'): string
    {
        return self::next($type);
    }

    public static function emailSubject($title, $reference): string
    {
        $title = trim((string) $title);
        $reference = trim((string) $reference);
        if ($reference === '') {
            return $title;
        }

        return '['.$reference.'] '.$title;
    }
}
