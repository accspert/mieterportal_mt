<?php

namespace App\Observers;

use App\Models\Document;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Unit;
use App\Notifications\DocumentCreatedNotification;

class DocumentObserver
{
    public function created(Document $document): void
    {
        // 1. If document is an invoice or utility bill, create/sync Invoice record for the Invoices table
        if (in_array($document->category, ['invoice', 'utility_bill'])) {
            $this->createInvoiceForDocument($document);
        } else {
            // 2. For other documents, send Document notification
            $this->notifyDocumentRecipients($document);
        }
    }

    public function updated(Document $document): void
    {
        if (in_array($document->category, ['invoice', 'utility_bill'])) {
            $this->createInvoiceForDocument($document);
        }
    }

    public function deleted(Document $document): void
    {
        if (in_array($document->category, ['invoice', 'utility_bill'])) {
            Invoice::where('notes', 'like', '%[DocID:'.$document->id.']%')
                ->orWhere('invoice_number', 'RE-'.date('Y').'-'.str_pad((string) $document->id, 5, '0', STR_PAD_LEFT))
                ->delete();
        }
    }

    public function createInvoiceForDocument(Document $document): void
    {
        $renterId = null;
        $ownerId = null;
        $unitId = null;

        if ($document->documentable_type === Renter::class) {
            $renterId = $document->documentable_id;
            $renter = Renter::find($renterId);
            $unitId = $renter?->units()->first()?->id;
        } elseif ($document->documentable_type === Unit::class) {
            $unit = Unit::find($document->documentable_id);
            $unitId = $unit?->id;
            $renterId = $unit?->current_renter_id;
            $ownerId = $unit?->property?->owner_id ?? null;
        } elseif ($document->documentable_type === Owner::class) {
            $ownerId = $document->documentable_id;
        } elseif ($document->documentable_type === Property::class) {
            $property = Property::find($document->documentable_id);
            $ownerId = $property?->owner_id ?? null;
        }

        if ($renterId || $ownerId) {
            $invoiceNumber = 'RE-'.date('Y').'-'.str_pad((string) $document->id, 5, '0', STR_PAD_LEFT);

            $existing = Invoice::where('invoice_number', $invoiceNumber)
                ->orWhere('notes', 'like', '%[DocID:'.$document->id.']%')
                ->first();

            $amount = $document->amount ?? 0.00;
            $dueDate = $document->due_date ?? now()->addDays(14);

            if (! $existing) {
                Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'renter_id' => $renterId,
                    'owner_id' => $ownerId,
                    'unit_id' => $unitId,
                    'amount' => $amount,
                    'due_date' => $dueDate,
                    'status' => 'sent',
                    'pdf_path' => $document->path,
                    'notes' => $document->filename.' [DocID:'.$document->id.']',
                ]);
            } else {
                $existing->update([
                    'renter_id' => $renterId ?: $existing->renter_id,
                    'owner_id' => $ownerId ?: $existing->owner_id,
                    'unit_id' => $unitId ?: $existing->unit_id,
                    'amount' => $document->amount !== null ? $document->amount : $existing->amount,
                    'due_date' => $document->due_date !== null ? $document->due_date : $existing->due_date,
                    'pdf_path' => $document->path,
                    'notes' => $document->filename.' [DocID:'.$document->id.']',
                ]);
            }
        }
    }

    protected function notifyDocumentRecipients(Document $document): void
    {
        $usersToNotify = collect();

        if ($document->documentable_type === Renter::class) {
            $renter = Renter::with('user')->find($document->documentable_id);
            if ($renter?->user) {
                $usersToNotify->push($renter->user);
            }
        } elseif ($document->documentable_type === Owner::class) {
            $owner = Owner::with('user')->find($document->documentable_id);
            if ($owner?->user) {
                $usersToNotify->push($owner->user);
            }
        } elseif ($document->documentable_type === Unit::class) {
            $unit = Unit::with(['currentRenter.user', 'property.owner.user'])->find($document->documentable_id);
            if ($unit?->currentRenter?->user) {
                $usersToNotify->push($unit->currentRenter->user);
            }
            if ($unit?->property?->owner?->user) {
                $usersToNotify->push($unit->property->owner->user);
            }
        } elseif ($document->documentable_type === Property::class) {
            $property = Property::with(['owner.user', 'units.currentRenter.user'])->find($document->documentable_id);
            if ($property?->owner?->user) {
                $usersToNotify->push($property->owner->user);
            }
            if ($property?->units) {
                foreach ($property->units as $u) {
                    if ($u->currentRenter?->user) {
                        $usersToNotify->push($u->currentRenter->user);
                    }
                }
            }
        }

        foreach ($usersToNotify->unique('id') as $user) {
            $user->notify(new DocumentCreatedNotification($document));
        }
    }
}
