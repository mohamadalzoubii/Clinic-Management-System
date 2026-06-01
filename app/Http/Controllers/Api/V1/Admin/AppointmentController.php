<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $date = trim((string) $request->query('date', ''));
        $partyType = $request->query('party_type'); // patient|doctor
        $partyId = $request->integer('party_id');
        $perPage = max(1, $request->integer('limit', 10));

        $appointments = Appointment::query()
            ->with(['patient.user', 'doctor.user'])
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($partyType === 'patient' && $partyId, function ($query) use ($partyId) {
                $query->where('patient_id', $partyId);
            })
            ->when($partyType === 'doctor' && $partyId, function ($query) use ($partyId) {
                $query->where('doctor_id', $partyId);
            })
            ->when($search !== '', function ($query) use ($search, $partyType) {
                $query->where(function ($scopedQuery) use ($search, $partyType) {
                    if ($partyType === 'patient') {
                        $scopedQuery->whereHas('patient.user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('last_name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });

                        return;
                    }

                    if ($partyType === 'doctor') {
                        $scopedQuery->whereHas('doctor.user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('last_name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });

                        return;
                    }

                    $scopedQuery
                        ->whereHas('patient.user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('last_name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('doctor.user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('last_name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($date !== '', function ($query) use ($date) {
                $parts = array_values(array_filter(array_map('trim', explode(',', $date))));
                if (count($parts) >= 2) {
                    $query->whereBetween('appointment_date', [$parts[0], $parts[1]]);

                    return;
                }

                $query->whereDate('appointment_date', $parts[0]);
            })
            ->latest()
            ->paginate($perPage);

        return AppointmentResource::collection($appointments);
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return $this->ok('Appointment deleted successfully.');
    }
}