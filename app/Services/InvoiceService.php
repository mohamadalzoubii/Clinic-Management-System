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

        $patientName = $invoice->user->first_name . ' ' . $invoice->user->last_name ?? 'N/A';
        $displayAmount = number_format(abs($invoice->amount ?? 0), 2);
        $typeIndicator = strtolower($invoice->status?->value ?? '') === 'refunded' ? 'Refunded Amount' : 'Amount';

        $qrData = "Invoice Number: {$invoice->invoice_number}\n"
            ."Patient: {$patientName}\n"
            ."{$typeIndicator}: \${$displayAmount}";

        $qrcode = base64_encode(
            QrCode::format('svg')
                ->size(120)
                ->errorCorrection('H')
                ->generate($qrData)
        );

        return Pdf::loadView('invoices.invoice_pdf', compact('invoice', 'qrcode'));
    }
}