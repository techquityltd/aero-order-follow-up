<?php

namespace Techquity\AeroOrderFollowUp;

use Aero\Events\ManagedHandler;
use Illuminate\Console\Scheduling\Schedule;
use Aero\Common\Facades\Settings;
use Aero\Common\Providers\ModuleServiceProvider;
use Techquity\AeroOrderFollowUp\Console\Commands\SendOrderFollowUpEmails;
use Techquity\AeroOrderFollowUp\Events\FirstOrderFollowUpEvent;
use Techquity\AeroOrderFollowUp\Events\SecondOrderFollowUpEvent;

class ServiceProvider extends ModuleServiceProvider
{
    protected $listen = [
        FirstOrderFollowUpEvent::class => [
            ManagedHandler::class,
        ],
        SecondOrderFollowUpEvent::class => [
            ManagedHandler::class,
        ],
    ];

    public function setup()
    {
        $this->loadSettings();
        $this->loadSchedule();
        $this->registerCommands();
    }

    private function loadSettings()
    {
        Settings::group('order-follow-up', function ($group) {
            $group->boolean('enabled')
                ->default(true);
        
            $group->string('first-email-item-skus-query')
                ->default('-SAMPLE,-FULL')
                ->hint('Defines part of the SKU to match when selecting orders for the first follow-up email. This is not a full SKU match but a "LIKE" query in the database, meaning it will match any SKU that **contains** one of these values. Use commas to separate multiple values (e.g., "-SAMPLE,-FULL").');
        
            $group->string('second-email-item-skus-query')
                ->default('-BOX')
                ->hint('Defines part of the SKU to check before sending the second follow-up email. If an order containing a SKU that includes one of these values is found, the second email will not be sent. This is also a "LIKE" query, so it matches any SKU that **contains** these values. Use commas to separate multiple values.');
        
            $group->integer('first-email-wait-time')
                ->default(7)
                ->hint('Number of days after the order is placed before sending the first follow-up email.');
        
            $group->integer('second-email-wait-time')
                ->default(21)
                ->hint('Number of days after the order is placed before sending the second follow-up email. This email will only be sent if the first email was sent and the customer has not placed an order containing a SKU that matches the "second-email-item-skus-query".');
        
            $group->string('queue')
                ->default('default')
                ->hint('Queue name used for processing follow-up email jobs.');
        
            $group->string('send-emails-cron')
                ->default('0 9 * * *')
                ->hint('Cron schedule for sending follow-up emails. The default runs daily at 9 AM.');
        });        
    }

    private function registerCommands()
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                SendOrderFollowUpEmails::class,
            ]);
        }
    }

    private function loadSchedule()
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            if (setting('order-follow-up.enabled')) {
                $schedule->command('order-follow-up:send-emails')->cron(setting('order-follow-up.send-emails-cron'));
            }
        });
    }
}
