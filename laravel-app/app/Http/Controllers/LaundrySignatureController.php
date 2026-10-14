<?php

namespace App\Http\Controllers;

use App\GeneralSetting;
use App\LaundryOrder;
use App\Services\LaundryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LaundrySignatureController extends Controller
{
    public function show($token)
    {
        $order = $this->findOpen($token);
        if (! $order) {
            return $this->expired();
        }

        return view('laundry.client_sign', [
            'order' => $order,
            'general_setting' => GeneralSetting::first(),
        ]);
    }

    public function sign(Request $request, $token)
    {
        $order = $this->findOpen($token);
        if (! $order) {
            return $this->expired();
        }

        $data = $request->validate([
            'collector_name' => 'required|string|max:191',
            'signature_data' => 'required|string',
        ]);

        $path = $this->storeSignature($order, $data['signature_data']);
        if (! $path) {
            return back()->with('not_permitted', 'Please draw a valid signature.')->withInput();
        }

        app(LaundryService::class)->completeCollection($order, $data['collector_name'], $path);

        return view('laundry.client_signed', [
            'order' => $order->fresh(['customer', 'items', 'sale']),
            'general_setting' => GeneralSetting::first(),
        ]);
    }

    public function scan($token)
    {
        $order = LaundryOrder::with(['customer', 'items', 'sale'])
            ->where('qr_token', trim((string) $token))
            ->first();
        if (! $order) {
            return response('Laundry ticket not found.', 404);
        }

        return view('laundry.scan', [
            'order' => $order,
            'general_setting' => GeneralSetting::first(),
        ]);
    }

    protected function findOpen($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return null;
        }
        $order = LaundryOrder::with(['customer', 'items', 'sale'])
            ->where('collect_token', $token)
            ->first();
        if (! $order || ! $order->isOpenForSignature()) {
            return null;
        }

        return $order;
    }

    protected function expired()
    {
        return response()->view('laundry.client_link_expired', [
            'general_setting' => GeneralSetting::first(),
        ], 410);
    }

    protected function storeSignature(LaundryOrder $order, $dataUrl)
    {
        if (! is_string($dataUrl) || ! preg_match('/^data:image\/(png|jpeg);base64,/', $dataUrl)) {
            return null;
        }
        $raw = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($raw, true);
        if ($binary === false || strlen($binary) < 80) {
            return null;
        }
        $dir = public_path('uploads/laundry/signatures');
        try {
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0775, true);
            }
        } catch (\Throwable $e) {
            Log::error('Laundry signature dir failed: '.$e->getMessage());

            return null;
        }
        $filename = 'lsig_'.$order->id.'_'.Str::random(10).'.png';
        $full = $dir.DIRECTORY_SEPARATOR.$filename;
        try {
            if (File::put($full, $binary) === false) {
                return null;
            }
        } catch (\Throwable $e) {
            Log::error('Laundry signature write failed: '.$e->getMessage());

            return null;
        }

        return 'uploads/laundry/signatures/'.$filename;
    }
}
