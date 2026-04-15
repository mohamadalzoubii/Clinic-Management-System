<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 13px;
        }

        .invoice-container {
            width: 100%;
            padding: 30px;
        }

        /* Layout Tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        .no-border-table td {
            border: none;
            vertical-align: top;
        }

        /* Header Section */
        .header-title {
            color: #2B6F71; /* 🔥 تم تغيير اللون هنا حسب طلبك */
            font-size: 28px;
            font-weight: bold;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }

        .clinic-info {
            color: #7f8c8d;
            font-size: 12px;
            line-height: 1.5;
        }

        .invoice-meta h1 {
            color: #34495e;
            font-size: 35px;
            margin: 0;
            text-transform: uppercase;
            font-weight: 300;
        }

        .meta-details {
            margin-top: 10px;
        }

        .meta-details td {
            padding: 3px 0;
        }

        /* Info Boxes (Patient & Doctor) */
        .info-section {
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #2B6F71; /* 🔥 تم توحيد لون الخط الجانبي مع لون الشعار */
            padding: 15px;
            border-radius: 4px;
        }

        .info-box h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 14px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 5px;
        }

        .info-box p {
            margin: 4px 0;
            color: #555;
        }

        .info-box strong {
            color: #333;
        }

        /* Data Tables (Billing & Medications) */
        .section-title {
            color: #2c3e50;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            margin-top: 20px;
        }

        .data-table {
            margin-bottom: 20px;
        }

        .data-table th {
            background-color: #34495e;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }

        .data-table td {
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
            color: #444;
        }

        .data-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        /* Totals Section */
        .totals-table {
            width: 40%;
            float: right;
            margin-top: 10px;
        }

        .totals-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
        }

        .totals-table .grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            background-color: #f8f9fa;
        }

        /* Clearfix for float */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* QR Code & Footer */
        .footer-section {
            margin-top: 50px;
            border-top: 2px solid #34495e;
            padding-top: 20px;
        }

        .qr-wrapper {
            float: left;
            width: 150px;
            text-align: center;
        }

        .qr-wrapper img {
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
        }

        .qr-text {
            font-size: 10px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .terms {
            float: right;
            width: 60%;
            color: #7f8c8d;
            font-size: 11px;
            line-height: 1.6;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: #2ecc71;
            color: white;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>
<body>

<div class="invoice-container">

    <table class="no-border-table">
        <tr>
            <td style="width: 50%;">
                <h2 class="header-title">Medics</h2>
                <div class="clinic-info">
                    123 Health Avenue, Medical District<br>
                    Amman, Jordan 11118<br>
                    Phone: +962 6 123 4567<br>
                    Email: info@medics-center.com
                </div>
            </td>
            <td style="width: 50%; text-align: right;" class="invoice-meta">
                <h1>INVOICE</h1>
                <table class="no-border-table meta-details text-right" style="width: 100%;">
                    <tr>
                        <td><strong>Invoice No:</strong></td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>Date Issued:</strong></td>
                        <td>{{ optional($invoice->created_at)->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Payment Status:</strong></td>
                        <td>
                            <span class="status-badge">
                                {{ strtoupper($invoice->status?->value ?? 'PAID') }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="no-border-table info-section">
        <tr>
            <td style="width: 48%; padding-right: 2%;">
                <div class="info-box">
                    <h3>BILL TO (PATIENT INFO)</h3>
                    <p><strong>Name:</strong> {{ $invoice->user?->name ?? 'N/A' }}</p>
                    <p><strong>Email:</strong> {{ $invoice->user?->email ?? 'N/A' }}</p>
                    <p><strong>Phone:</strong> {{ $invoice->user?->phone ?? 'N/A' }}</p>
                </div>
            </td>

            <td style="width: 48%; padding-left: 2%;">
                <div class="info-box" style="border-left-color: #2ecc71;">
                    <h3>ATTENDING PHYSICIAN</h3>
                    <p><strong>Dr. Name:</strong> Dr. {{ $invoice->appointment?->doctor?->user?->name ?? 'Unknown' }}</p>
                    <p>
                        <strong>Specialty:</strong> {{ $invoice->appointment?->doctor?->specialization ?? 'General Practice' }}
                    </p>
                    <p><strong>Consultation Date:</strong>
                        {{ $invoice->appointment?->appointment_date ? \Carbon\Carbon::parse($invoice->appointment->appointment_date)->format('d M Y - h:i A') : 'N/A' }}
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Consultation & Services</div>
    <table class="data-table">
        <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 55%;">Description</th>
            <th class="text-center" style="width: 15%;">Qty</th>
            <th class="text-right" style="width: 25%;">Amount (JOD)</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>1</td>
            <td>
                <strong>Medical Consultation</strong><br>
                <span style="font-size: 11px; color: #7f8c8d;">Standard checkup and diagnosis</span>
            </td>
            <td class="text-center">1</td>
            <td class="text-right">{{ number_format($invoice->amount ?? 0, 2) }}</td>
        </tr>
        </tbody>
    </table>

    <div class="section-title">Prescribed Medications (Rx)</div>
    <table class="data-table">
        <thead>
        <tr>
            <th style="width: 30%;">Medication Name</th>
            <th style="width: 25%;">Dosage</th>
            <th style="width: 25%;">Frequency</th>
            <th style="width: 20%;">Duration</th>
        </tr>
        </thead>
        <tbody>
        {{-- 🔥 طريقة احترافية للتحقق من وجود العلاقات بالكامل بدون إيرور --}}
        @if($invoice->appointment?->consultation?->prescriptionItems?->count() > 0)

            @foreach($invoice->appointment->consultation->prescriptionItems as $item)
                <tr>
                    <td><strong>{{ $item->medicine_name }}</strong></td>
                    <td>{{ $item->dosage }}</td>
                    <td>{{ $item->frequency }}</td>
                    <td>{{ $item->duration }}</td>
                </tr>
            @endforeach

        @else
            <tr>
                <td colspan="4" class="text-center" style="color: #95a5a6; padding: 20px;">
                    No medications prescribed during this consultation.
                </td>
            </tr>
        @endif
        </tbody>
    </table>

    <div class="clearfix">
        <table class="totals-table">
            <tr>
                <td>Subtotal</td>
                <td class="text-right">{{ number_format($invoice->amount ?? 0, 2) }} JOD</td>
            </tr>
            <tr>
                <td>Tax (0%)</td>
                <td class="text-right">0.00 JOD</td>
            </tr>
            <tr class="grand-total">
                <td>Total Paid</td>
                <td class="text-right">{{ number_format($invoice->amount ?? 0, 2) }} JOD</td>
            </tr>
        </table>
    </div>

    <div class="footer-section clearfix">
        <div class="qr-wrapper">
            <img src="data:image/svg+xml;base64,{{ $qrcode }}" alt="QR Code">
            <div class="qr-text">Scan to Verify</div>
        </div>

        <div class="terms">
            <strong>Terms & Conditions:</strong><br>
            1. This invoice is computer generated and does not require a physical signature.<br>
            2. Prescribed medications should be taken strictly as directed by the physician.<br>
            3. For any medical emergencies following the consultation, please visit the nearest ER.<br><br>
            <em>Thank you for trusting Medics. Wishing you a speedy recovery!</em>
        </div>
    </div>

</div>

</body>
</html>
