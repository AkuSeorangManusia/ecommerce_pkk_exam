<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Models\Product;

class OrderItemObserver
{
    /**
     * Handle the OrderItem "created" event.
     * Decrease product stock when an order item is created.
     */
    public function created(OrderItem $orderItem): void
    {
        $product = Product::find($orderItem->product_id);
        
        if ($product && $product->track_stock) {
            $product->decrement('stock', $orderItem->quantity);
        }
    }

    /**
     * Handle the OrderItem "updated" event.
     * Adjust stock if quantity changes.
     */
    public function updated(OrderItem $orderItem): void
    {
        if ($orderItem->isDirty('quantity')) {
            $product = Product::find($orderItem->product_id);
            
            if ($product && $product->track_stock) {
                $oldQuantity = $orderItem->getOriginal('quantity');
                $newQuantity = $orderItem->quantity;
                $difference = $oldQuantity - $newQuantity;
                
                // If difference is positive, we're reducing quantity (restore stock)
                // If difference is negative, we're increasing quantity (reduce stock)
                if ($difference > 0) {
                    $product->increment('stock', $difference);
                } else {
                    $product->decrement('stock', abs($difference));
                }
            }
        }
    }

    /**
     * Handle the OrderItem "deleted" event.
     * Restore product stock when an order item is deleted.
     */
    public function deleted(OrderItem $orderItem): void
    {
        $product = Product::find($orderItem->product_id);
        
        if ($product && $product->track_stock) {
            $product->increment('stock', $orderItem->quantity);
        }
    }
}
