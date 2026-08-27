<?php

namespace App\Http\Controllers;

use App\Services\MembershipService;
use App\Support\MembershipQr;
use Illuminate\Http\Request;

class MembershipPosController extends Controller
{
    public function scan(Request $request)
    {
        $membership = MembershipQr::findByScan($request->get('q'));
        if (! $membership || ! $membership->customer_id) {
            return response()->json(['ok' => false]);
        }

        return response()->json([
            'ok' => true,
            'customer_id' => $membership->customer_id,
            'number' => $membership->number,
            'name' => optional($membership->customer)->name,
            'status' => $membership->status,
        ]);
    }

    public function benefit($customerId, $productId)
    {
        $service = app(MembershipService::class);
        $membership = $service->activeMembershipForCustomer($customerId);
        if (! $membership) {
            return response()->json(['ok' => false]);
        }
        $benefit = $service->benefitForProduct($membership, $productId);
        if (! $benefit) {
            return response()->json(['ok' => false]);
        }

        return response()->json([
            'ok' => true,
            'kind' => $benefit->kind,
            'member_price' => $benefit->member_price,
            'label' => $benefit->kind === 'free' ? 'FREE MEMBER BENEFIT' : 'MEMBER PRICE',
        ]);
    }
}
