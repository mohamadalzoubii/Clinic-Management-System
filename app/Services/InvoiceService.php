<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvoiceService
{
    public function generatePdf(Invoice $invoice)
    {

        $invoice->loadMissing(['user', 'appointment.doctor.user', 'appointment.consultation.prescriptionItems']);

        $qrData = "Invoice Number: {$invoice->invoice_number}\n"
            ."Patient: {$invoice->user->name}\n"
            ."Amount: {$invoice->amount}";

        $qrcode = base64_encode(
            QrCode::format('svg')
                ->size(120)
                ->errorCorrection('H')
                ->generate($qrData)
        );

        return Pdf::loadView('invoices.invoice_pdf', compact('invoice', 'qrcode'));
    }
}
