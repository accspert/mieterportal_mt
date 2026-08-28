<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInvoiceRequest;
use App\Http\Requests\Api\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Invoice::query()->with(['renter', 'owner', 'unit']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        $perPage = (int) $request->get('per_page', 15);
        $invoices = $query->latest('due_date')->paginate($perPage);

        return InvoiceResource::collection($invoices);
    }

    public function store(StoreInvoiceRequest $request): InvoiceResource
    {
        $invoice = Invoice::create($request->validated());
        $invoice->load(['renter', 'owner', 'unit']);

        return new InvoiceResource($invoice);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        $invoice->load(['renter', 'owner', 'unit']);

        return new InvoiceResource($invoice);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        $invoice->update($request->validated());
        $invoice->load(['renter', 'owner', 'unit']);

        return new InvoiceResource($invoice);
    }

    public function pdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        return $pdfService->streamResponse($invoice);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json([], 204);
    }
}
