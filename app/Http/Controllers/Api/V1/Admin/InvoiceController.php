<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = max(1, $request->integer('limit', 10));

        $query = Invoice::query()->with(['user.patient']);

        if ($patientId = $request->integer('patient_id')) {
            $query->whereHas('user.patient', function ($q) use ($patientId) {
                $q->where('id', $patientId);
            });
        }

        if ($search = $request->string('search')) {
            $like = '%' . trim($search) . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('user', function ($q2) use ($like) {
                    $q2->where('email', 'like', $like);
                })->orWhereHas('user.patient', function ($q3) use ($like) {
                    $q3->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like);
                });
            });
        }

        $invoices = $query->latest('id')->paginate($perPage, ['*'], 'page', $page);

        $items = collect($invoices->items())->map(function (Invoice $invoice) {
            $patientUser = $invoice->user?->patient?->user;

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'status' => $invoice->status?->value ?? $invoice->status,
                'entry_type' => $invoice->entry_type,
                'paid_at' => $invoice->paid_at?->toISOString(),
                'download_url' => url("/api/v1/invoices/{$invoice->id}/download"),
                'patient' => [
                    'id' => $invoice->user?->patient?->id,
                    'first_name' => $patientUser?->first_name,
                    'last_name' => $patientUser?->last_name,
                    'email' => $patientUser?->email,
                ],
            ];
        });

        return $this->ok('Invoices retrieved successfully.', [
            'invoices' => $items,
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }
}
