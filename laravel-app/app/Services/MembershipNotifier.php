<?php

namespace App\Services;

use App\Membership;
use App\MembershipApplication;
use App\MembershipNotification;
use App\Services\Messaging\NotificationRouter;
use App\Support\MembershipQr;
use App\Support\WhatsAppMessage;
use App\User;
use Illuminate\Support\Facades\Log;

class MembershipNotifier
{
    protected $router;

    public function __construct(NotificationRouter $router)
    {
        $this->router = $router;
    }

    public function sendPhone($phone, $message, array $vars = [])
    {
        if (! $phone) {
            return ['success' => false];
        }

        return $this->router->sendWhatsAppText($phone, $message, $vars);
    }

    public function applicationReceived(MembershipApplication $application)
    {
        $this->sendPhone($application->phone, WhatsAppMessage::membershipApplicationReceived(
            $application->full_name,
            $application->reference
        ), [
            'title' => 'Membership application',
            'message' => 'Your membership application has been received.',
            'details' => $application->reference,
        ]);
        $this->notifyAdmins($application);
    }

    public function notifyAdmins(MembershipApplication $application)
    {
        $path = url('/admin/membership/applications/'.$application->id);
        $loginUrl = url('/login?redirect='.rawurlencode('/admin/membership/applications/'.$application->id));
        $admins = User::where('is_deleted', false)->where('is_active', 1)->where('role_id', '<=', 2)
            ->whereNotNull('phone')->where('phone', '!=', '')->get();
        foreach ($admins as $admin) {
            $this->sendPhone($admin->phone, WhatsAppMessage::membershipApplicationAdmin(
                $admin->name,
                $application->full_name,
                $application->reference,
                $loginUrl
            ), [
                'title' => 'New membership application',
                'message' => $application->full_name.' applied',
                'details' => $application->reference,
            ]);
        }
        app(MembershipService::class)->notifyAdmins(
            'New membership application '.$application->reference.' from '.$application->full_name,
            $path
        );
    }

    public function approved(Membership $membership, $payUrl = null)
    {
        $customer = $membership->customer;
        $phone = $customer ? $customer->phone_number : optional($membership->application)->phone;
        $name = $customer ? $customer->name : optional($membership->application)->full_name;
        $this->sendPhone($phone, WhatsAppMessage::membershipApproved(
            $name,
            $membership->number,
            $membership->status,
            optional($membership->expires_at)->toFormattedDateString(),
            $payUrl,
            MembershipQr::verifyUrl($membership)
        ));
    }

    public function rejected(MembershipApplication $application)
    {
        $this->sendPhone($application->phone, WhatsAppMessage::membershipRejected(
            $application->full_name,
            $application->reference,
            $application->admin_note
        ));
    }

    public function moreInfo(MembershipApplication $application)
    {
        $this->sendPhone($application->phone, WhatsAppMessage::membershipMoreInfo(
            $application->full_name,
            $application->reference,
            $application->admin_note ?: 'Please contact us on WhatsApp with the missing documents.'
        ));
    }

    public function reminder(Membership $membership, $kind)
    {
        $exists = MembershipNotification::where('membership_id', $membership->id)->where('kind', $kind)
            ->whereDate('created_at', now()->toDateString())->first();
        if ($exists) {
            return;
        }
        $customer = $membership->customer;
        if (! $customer || ! $customer->phone_number) {
            return;
        }
        $result = $this->sendPhone($customer->phone_number, WhatsAppMessage::membershipRenewalReminder(
            $customer->name,
            $membership->number,
            optional($membership->expires_at)->toFormattedDateString(),
            MembershipQr::renewUrl($membership),
            $kind
        ));
        MembershipNotification::create([
            'membership_id' => $membership->id,
            'kind' => $kind,
            'channel' => 'whatsapp',
            'status' => ! empty($result['success']) ? 'sent' : 'failed',
            'payload' => json_encode($result),
        ]);
    }
}
