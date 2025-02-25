<?php

namespace Techquity\AeroOrderFollowUp\Console\Commands;

use Illuminate\Console\Command;
use Techquity\AeroOrderFollowUp\Jobs\SendOrderFollowUpEmailsJob;

class SendOrderFollowUpEmails extends Command
{
    protected $signature = 'order-follow-up:send-emails';
    protected $description = 'Send follow up emails to customers based on their schedule.';

    public function handle()
    {
        dispatch(new SendOrderFollowUpEmailsJob())->onQueue(setting('order-follow-up.queue'));
    }
}
