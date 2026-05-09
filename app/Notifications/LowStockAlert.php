<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification
{
    public function __construct(
        public readonly Product $product,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $current = $this->product->totalStock();
        $shortage = abs($current - $this->product->min_stock);

        return (new MailMessage)
            ->subject("⚠️ Low Stock Alert — {$this->product->name}")
            ->markdown('emails.low-stock', [
                'product' => $this->product,
                'notifiable' => $notifiable,
                'current' => $current,
                'shortage' => $shortage,
            ]);
    }

    /**
     * Expose notification data for the database channel (future use).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'current_stock' => $this->product->totalStock(),
            'min_stock' => $this->product->min_stock,
        ];
    }
}
