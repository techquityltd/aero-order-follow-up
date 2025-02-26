<?php

namespace Techquity\AeroOrderFollowUp\Events;

use Aero\Events\ManagedEvent;
use Aero\Cart\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class FirstOrderFollowUp extends ManagedEvent
{
    use Queueable, SerializesModels;

    public static $variables = [
        'order',
    ];

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function getNotifiable()
    {
        return $this->order->email;
    }
}
