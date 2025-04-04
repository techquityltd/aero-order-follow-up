<?php

namespace Techquity\AeroOrderFollowUp\Jobs;

use Aero\Cart\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Techquity\AeroOrderFollowUp\Events\FirstOrderFollowUp;
use Techquity\AeroOrderFollowUp\Events\SecondOrderFollowUp;

class SendOrderFollowUpEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        if (!setting('order-follow-up.enabled')) {
            return;
        }

        // STAGE 1: Send first follow-up emails (7 days ago)
        $this->sendFirstFollowUpEmails();
        // STAGE 2: Send second follow-up emails (21 days ago, only if first was sent)
        $this->sendSecondFollowUpEmails();
    }

    private function sendFirstFollowUpEmails()
    {
        $waitDays = setting('order-follow-up.first-email-wait-time');
        $skuQueries = explode(',', setting('order-follow-up.first-email-item-skus-query'));
        $dateCheck = Carbon::now()->subDays($waitDays);

        $orders = Order::whereDate('ordered_at', $dateCheck)
            ->whereHas('items', function ($query) use ($skuQueries) {
                foreach ($skuQueries as $sku) {
                    $query->orWhere('sku', 'LIKE', '%' . trim($sku));
                }
            })
            ->get();

        foreach ($orders as $order) {
            // Check if first email was already sent
            if ($order->additional('first_follow_up_email')) {
                continue; // Skip if already sent
            }

            // Send the first follow-up email
            event(new FirstOrderFollowUp ($order));

            // Store the first follow-up email timestamp
            $order->additional('first_follow_up_email', Carbon::now()->toDateTimeString());
        }
    }

    private function sendSecondFollowUpEmails()
    {
        $firstEmailWaitDays = setting('order-follow-up.first-email-wait-time');
        $secondEmailWaitDays = setting('order-follow-up.second-email-wait-time');
        $firstEmailSkus = explode(',', setting('order-follow-up.first-email-item-skus-query'));
        $secondEmailSkus = explode(',', setting('order-follow-up.second-email-item-skus-query'));

        $dateCheck = Carbon::now()->subDays($secondEmailWaitDays);

        $orders = Order::whereDate('ordered_at', $dateCheck)
            ->whereHas('items', function ($query) use ($firstEmailSkus) {
                foreach ($firstEmailSkus as $sku) {
                    $query->orWhere('sku', 'LIKE', '%' . trim($sku));
                }
            })
            ->get();

        foreach ($orders as $order) {

            if (!$order->additional('first_follow_up_email') || $order->additional('second_follow_up_email')) {
                continue;
            }

            // Check if an order containing second email SKUs has been placed
            $hasOrderedBox = Order::where('email', $order->email)
                ->where('ordered_at', '>', Carbon::now()->subDays($firstEmailWaitDays))
                ->whereHas('items', function ($query) use ($secondEmailSkus) {
                    foreach ($secondEmailSkus as $sku) {
                        $query->orWhere('sku', 'LIKE', '%' . trim($sku));
                    }
                })
                ->exists();

            if (!$hasOrderedBox) {

                // Send the second follow-up email
                event(new SecondOrderFollowUp($order));

                // Store the second follow-up email timestamp
                $order->additional('second_follow_up_email', Carbon::now()->toDateTimeString());
            }
        }
    }
}
