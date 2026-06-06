<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Appointment\GetAppointmentsAction;
use App\DTOs\Appointment\IndexAppointmentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAppointmentsRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    use ApiResponses;

    public function index(
        IndexAppointmentsRequest $request,
        GetAppointmentsAction $action,
    ): AnonymousResourceCollection {
        $data = IndexAppointmentData::fromRequest($request);

        $appointments = $action->execute($data);

        return AppointmentResource::collection($appointments);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return $this->ok('Appointment deleted successfully.');
    }
}
