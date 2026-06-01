<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Patient;
use App\Services\Admin\PatientService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class PatientPackageController extends Controller
{
    use ApiResponses;

    public function buyPackageForPatient(Request $request, Patient $patient, PatientService $service)
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
        ]);

        $package = Package::findOrFail($data['package_id']);
        $result = $service->buyPackageForPatient($patient->user, $package);

        return $this->ok('Package purchased successfully.', [
            'current_balance' => $result['user']->wallet_balance,
            'patient_id' => $patient->id,
            'package_id' => $package->id,
            'invoice_id' => $result['invoice']->id,
            'invoice_number' => $result['invoice']->invoice_number,
        ]);
    }
}