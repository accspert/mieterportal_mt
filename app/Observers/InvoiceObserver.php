<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Notifications\InvoiceDueNotification;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        // Notify renter
        if ($invoice->renter && $invoice->renter->user) {
            $invoice->renter->user->notify(new InvoiceDueNotification($invoice));
        }

        // Notify owner
        if ($invoice->owner && $invoice->owner->user) {
            $invoice->owner->user->notify(new InvoiceDueNotification($invoice));
        }
    }
}
