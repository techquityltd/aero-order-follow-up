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
        'order', 'parentProducts', 'storeUrl'
    ];

    public $order;
    public $parentProducts;
    public $storeUrl;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->parentProducts = $this->getParentProducts();
        $this->storeUrl = config('app.url');
    }

    public function getParentProducts()
    {
        $flaggedSkus = array_map('trim', explode(',', setting('order-follow-up.first-email-item-skus-query')));
        $sampleProducts = $this->order->items->filter(function ($item) use ($flaggedSkus) {
            foreach ($flaggedSkus as $sku) {
                if (str_contains($item->sku, $sku)) {
                    return true;
                }
            }
            return false;
        })->values()->toArray();

        foreach ($sampleProducts as $product) {
            $parentSkus[] = [
                'sku' => str_replace($flaggedSkus, '', $product['sku']),
            ];
        }

        return Product::whereIn('model', $parentSkus)->get();
    }

    public function getNotifiable()
    {
        return $this->order->email;
    }
}
