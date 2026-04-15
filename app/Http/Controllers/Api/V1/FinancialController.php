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

        $updatedUser = $this->financialService->buyPackage($request->user(), $package);

        return $this->ok(
            ['current_balance' => $updatedUser->wallet_balance],
            'Wallet recharged successfully.'
        );
    }

    public function downloadInvoice(Invoice $invoice)
    {
        $pdf = $this->invoiceService->generatePdf($invoice);

        return $pdf->download("invoice_{$invoice->invoice_number}.pdf");

    }
}
