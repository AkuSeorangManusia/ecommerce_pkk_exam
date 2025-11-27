<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'subtotal',
        'tax',
        'shipping_cost',
        'discount',
        'total',
        'status',
        'payment_status',
        'payment_method',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'notes',
        'admin_notes',
        'paid_at',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
            
            // Calculate tax at 12% if not set
            if ($order->subtotal > 0 && empty($order->tax)) {
                $order->tax = $order->subtotal * 0.12;
            }
            
            // Calculate total
            $order->total = $order->subtotal + $order->tax + ($order->shipping_cost ?? 0) - ($order->discount ?? 0);
        });

        static::updating(function ($order) {
            // Auto-set paid_at when payment_status changes to paid
            if ($order->isDirty('payment_status') && $order->payment_status === 'paid' && empty($order->paid_at)) {
                $order->paid_at = now();
            }

            // Auto-set shipped_at when status changes to shipped
            if ($order->isDirty('status') && $order->status === 'shipped' && empty($order->shipped_at)) {
                $order->shipped_at = now();
            }

            // Auto-set delivered_at when status changes to delivered
            if ($order->isDirty('status') && $order->status === 'delivered' && empty($order->delivered_at)) {
                $order->delivered_at = now();
                // Also set shipped_at if not set
                if (empty($order->shipped_at)) {
                    $order->shipped_at = now();
                }
            }

            // Recalculate tax at 12% if subtotal changed
            if ($order->isDirty('subtotal')) {
                $order->tax = $order->subtotal * 0.12;
            }

            // Recalculate total
            $order->total = $order->subtotal + $order->tax + ($order->shipping_cost ?? 0) - ($order->discount ?? 0);
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items()->sum('quantity');
    }

    public function getFullShippingAddressAttribute(): string
    {
        $parts = array_filter([
            $this->shipping_address,
            $this->shipping_city,
            $this->shipping_state,
            $this->shipping_postal_code,
            $this->shipping_country,
        ]);

        return implode(', ', $parts);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('subtotal');
        $this->tax = $this->subtotal * 0.12; // 12% tax
        $this->total = $this->subtotal + $this->tax + ($this->shipping_cost ?? 0) - ($this->discount ?? 0);
    }

    public function recalculateFromItems(): void
    {
        $this->calculateTotals();
        $this->save();
    }

    public function markAsPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function markAsShipped(): void
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }
}