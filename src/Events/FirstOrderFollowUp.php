<?php

namespace Techquity\AeroOrderFollowUp\Events;

use Aero\Events\ManagedEvent;
use Aero\Cart\Models\Order;
use Illuminate\Bus\Queueable;
use Aero\Catalog\Models\Variant;
use Aero\Catalog\Models\Product;
use Illuminate\Queue\SerializesModels;

class FirstOrderFollowUp extends ManagedEvent
{
    use Queueable, SerializesModels;

    public static $variables = [
        'order', 'parentProducts'
    ];

    public $order;
    public $parentProducts;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->parentProducts = $this->getParentProducts();
    }

    public function getParentProducts()
    {
        $order = Order::find($this->order->id);
        $parentSkus = [];

        $sampleProducts = array_filter($order->items->toArray(), function($product) {
            return str_contains($product['sku'], '-SAMPLE');
        });

        foreach ($sampleProducts as $product) {
            $parentSkus[] = [
                'sku' => str_replace('-SAMPLE', '', $product['sku']),
            ];
        }

        return Product::whereIn('model', $parentSkus)->get();

    }

    public function getNotifiable()
    {
        return $this->order->email;
    }
}
