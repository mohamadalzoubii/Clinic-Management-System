<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Package;
use App\Services\FinancialService;
use App\Services\InvoiceService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected FinancialService $financialService,
        protected InvoiceService $invoiceService
    ) {}

    public function purchasePackage(Request $request)
    {
        $request->validate(['package_id' => 'required|exists:packages,id']);

        $package = Package::find($request->package_id);

        $result = $this->financialService->buyPackageForUser($request->user(), $package);

        return $this->ok(
            'Wallet recharged successfully.',
            [
                'current_balance' => $result['user']->wallet_balance,
                'invoice_id' => $result['invoice']->id,
                'invoice_number' => $result['invoice']->invoice_number,
            ]
        );
    }

    public function index(Request $request)
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = max(1, $request->integer('limit', 10));

        $invoices = $request->user()
            ->invoices()
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($invoices->items())->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'status' => $invoice->status?->value ?? $invoice->status,
                'entry_type' => $invoice->entry_type,
                'paid_at' => $invoice->paid_at?->toISOString(),
                'appointment_id' => $invoice->appointment_id,
                'download_url' => url("/api/v1/invoices/{$invoice->id}/download"),
            ]);

        return $this->ok('Invoices retrieved successfully.', [
            'invoices' => $items,
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function downloadInvoice(Invoice $invoice)
    {
        $pdf = $this->invoiceService->generatePdf($invoice);

        return $pdf->download("invoice_{$invoice->invoice_number}.pdf");

    }
}
