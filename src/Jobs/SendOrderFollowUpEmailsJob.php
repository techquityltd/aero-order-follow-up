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
        if (setting('order-follow-up.send-second-email')) {
            $this->sendSecondFollowUpEmails();
        }
    }

    private function sendFirstFollowUpEmails()
    {
        $waitDays = setting('order-follow-up.first-email-wait-time');
        $skuQueries = array_map('trim', explode(',', setting('order-follow-up.first-email-item-skus-query')));
        $dateCheck = Carbon::now()->subDays($waitDays);

        $orders = Order::visible()
            ->whereDate('ordered_at', $dateCheck)
            ->get();

        foreach ($orders as $order) {
            // Check if first email was already sent
            if ($order->additional('first_follow_up_email')) {
                continue; // Skip if already sent
            }

            // Only proceed if the order contains at least one of the SKUs
            $hasSku = false;
            foreach ($order->items as $item) {
                foreach ($skuQueries as $sku) {
                    if (stripos($item->sku, $sku) !== false) {
                        $hasSku = true;
                        break 2;
                    }
                }
            }
            if (!$hasSku) {
                continue;
            }

            // Send the first follow-up email
            event(new FirstOrderFollowUp($order));

            // Store the first follow-up email timestamp
            $order->additional('first_follow_up_email', Carbon::now()->toDateTimeString());
        }
    }

    private function sendSecondFollowUpEmails()
    {
        $firstEmailWaitDays = setting('order-follow-up.first-email-wait-time');
        $secondEmailWaitDays = setting('order-follow-up.second-email-wait-time');
        $firstEmailSkus = array_map('trim', explode(',', setting('order-follow-up.first-email-item-skus-query')));
        $secondEmailSkus = array_map('trim', explode(',', setting('order-follow-up.second-email-item-skus-query')));

        $dateCheck = Carbon::now()->subDays($secondEmailWaitDays);

        $orders = Order::visible()
            ->whereDate('ordered_at', $dateCheck)
            ->get();

            foreach ($orders as $order) {
            if (!$order->additional('first_follow_up_email') || $order->additional('second_follow_up_email')) {
                continue;
            }

            // Only proceed if the order contains at least one of the first email SKUs
            $hasFirstSku = false;
            foreach ($order->items as $item) {
                foreach ($firstEmailSkus as $sku) {
                    if (stripos($item->sku, $sku) !== false) {
                        $hasFirstSku = true;
                        break 2;
                    }
                }
            }
            if (!$hasFirstSku) {
                continue;
            }

            // Check if an order containing second email SKUs has been placed
            $hasOrderedBox = Order::visible()
                ->where('email', $order->email)
                ->where('ordered_at', '>', Carbon::now()->subDays($firstEmailWaitDays))
                ->get()
                ->filter(function ($otherOrder) use ($secondEmailSkus) {
                    foreach ($otherOrder->items as $item) {
                        foreach ($secondEmailSkus as $sku) {
                            if (stripos($item->sku, $sku) !== false) {
                                return true;
                            }
                        }
                    }
                    return false;
                })
                ->isNotEmpty();

            if (!$hasOrderedBox) {
                // Send the second follow-up email
                event(new SecondOrderFollowUp($order));

                // Store the second follow-up email timestamp
                $order->additional('second_follow_up_email', Carbon::now()->toDateTimeString());
            }
        }
    }
}
