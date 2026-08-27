<?php

namespace App\Console\Commands;

use App\Services\MembershipService;
use Illuminate\Console\Command;

class ProcessMemberships extends Command
{
    protected $signature = 'memberships:process';

    protected $description = 'Expire memberships, flag expiring members, and send WhatsApp renewal reminders';

    public function handle(MembershipService $memberships)
    {
        $memberships->processExpiry();
        $memberships->sendReminders();
        $this->info('Memberships processed.');

        return 0;
    }
}
