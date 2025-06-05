<?php

namespace Techquity\AeroOrderFollowUp\Events;

use Aero\Events\ManagedEvent;
use Aero\Cart\Models\Order;
use Illuminate\Bus\Queueable;
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
        $secondEmailSuffixes = array_map('trim', explode(',', setting('order-follow-up.second-email-item-skus-query')));

        $sampleProducts = $this->order->items->filter(function ($item) use ($flaggedSkus) {
            foreach ($flaggedSkus as $suffix) {
                if (str_ends_with($item->sku, $suffix)) {
                    return true;
                }
            }
            return false;
        });

        $parentSkus = $sampleProducts->flatMap(function ($item) use ($flaggedSkus, $secondEmailSuffixes) {
            foreach ($flaggedSkus as $suffix) {
                if (str_ends_with($item->sku, $suffix)) {
                    $baseSku = str_replace($suffix, '', $item->sku);
        
                    // Append each second-email suffix to the stripped SKU
                    return collect($secondEmailSuffixes)->map(function ($secondSuffix) use ($baseSku) {
                        return $baseSku . $secondSuffix;
                    });
                }
            }
            return [];
        })->unique()->values()->all();

        return Product::whereIn('model', $parentSkus)->get();
    }

    public function getNotifiable()
    {
        return $this->order->email;
    }
}
