<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mailnotificatie aan admins voor een product onder zijn minimumvoorraad.
 *
 * Bewust géén ShouldQueue op deze klasse: ze wordt al verstuurd vanuit de queued
 * listener SendLowStockNotification — een tweede queue-laag zou alleen extra
 * vertraging en complexiteit toevoegen.
 */
class LowStockAlert extends Notification
{
    /** Het product dat onder zijn drempel zakte; readonly, de notificatie wijzigt zelf niets. */
    public function __construct(
        public readonly Product $product,
    ) {}

    /** Bepaalt de afleverkanalen: deze waarschuwing vertrekt momenteel enkel via e-mail. */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Stelt de waarschuwingsmail samen: berekent de actuele stand en het tekort,
     * en rendert die cijfers in de markdown-template emails.low-stock.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $current = $this->product->totalStock();
        // current ligt per definitie onder min_stock, dus het verschil is negatief;
        // abs() toont het tekort in de mail als positief getal.
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
     * Payload voor het database-kanaal (nu nog niet actief in via()): maakt het
     * mogelijk om later in-app meldingen te tonen zonder deze klasse te herwerken.
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
