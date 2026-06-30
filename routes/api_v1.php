<?php

use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\InvoiceController;
use App\Http\Controllers\Api\V1\Admin\PackageController;
use App\Http\Controllers\Api\V1\Admin\ScheduleController;
use App\Http\Controllers\Api\V1\Admin\SecretaryController;
use App\Http\Controllers\Api\V1\Admin\VacationController as AdminVacationController;
use App\Http\Controllers\Api\V1\AiChatController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\ConsultationController;
use App\Http\Controllers\Api\V1\Doctor\VacationController as DoctorVacationController;
use App\Http\Controllers\Api\V1\DoctorAvailabilityController;
use App\Http\Controllers\Api\V1\FinancialController;
use App\Http\Controllers\Api\V1\PatientMediaController;
use App\Http\Controllers\Api\V1\PatientPackageController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verify']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/doctors/login', [AuthController::class, 'DoctorLogin']);
Route::post('/admin/login', [AuthController::class, 'AdminLogin']);
Route::post('/secretary/login', [AuthController::class, 'SecretaryLogin']);

Route::middleware(['auth:sanctum', 'role:admin|secretary|patient'])->group(function () {
    Route::controller(PackageController::class)->prefix('packages')->group(function () {
        Route::get('/', 'index');
        Route::get('/{package}', 'show');
    });
});

Route::middleware('auth:sanctum')->group(function () {

    Route::middleware('role:patient')->group(function () {

        Route::prefix('patient')->controller(PatientController::class)->group(function () {
            Route::post('/CompleteProftile', 'completeProfile');
            Route::put('updateprofile/{patient}', 'updateProfile');
            Route::get('/appointments', 'appointments');
            Route::get('/{patient}/visit-summary', 'visitSummary');
            Route::get('/prescriptions', 'prescriptions');
        });

        Route::prefix('patient/media')->group(function () {
            Route::get('/xrays', [PatientMediaController::class, 'xrays']);
            Route::get('/medical-tests', [PatientMediaController::class, 'medicalTests']);
        });

        Route::prefix('doctors')->controller(DoctorController::class)->group(function () {
            Route::post('/{doctor}/reviews', 'storeReview');
            Route::get('/{doctor}/reviews', 'doctorReviews');
        });

        Route::prefix('appointments')->controller(AppointmentController::class)->group(function () {
            Route::post('/storeAppointment', 'store');
            Route::patch('/{appointment}/update', 'cansal');
        });

        Route::get('patient/chat/doctors-threads',
            [ChatController::class, 'getDoctorThreads']);
    });
    Route::get('/patients/{patient}/media/xrays', [PatientMediaController::class, 'getPatientXrays']);
    Route::get('/patients/{patient}/media/medical-tests', [PatientMediaController::class, 'getPatientMedicalTests']);

    Route::middleware('role:admin|doctor|secretary')->group(function () {

        Route::get('/patients', [PatientController::class, 'index']);
        Route::get('/patients/{patient}', [PatientController::class, 'show']);

        // Added: Allow clinical staff to read specific patient visual history archives

        Route::get('/doctors/dashboard', [DoctorController::class, 'dashboard']);
        Route::get('/doctors/summary', [DoctorController::class, 'summary']);
        Route::get('/doctors/summary/patients/{patient}/appointments',
            [DoctorController::class, 'summaryPatientAppointments']);

        Route::get('/available-days',
            [DoctorAvailabilityController::class, 'getAvailableDays']);

        Route::get('/doctors/{doctor}/vacation-constraints', [DoctorVacationController::class, 'constraints']);

        Route::get('/available-slots',
            [DoctorAvailabilityController::class, 'getAvailableSlots']);

        Route::get('/doctor/agenda', [DoctorAvailabilityController::class, 'getAgenda']);

        Route::prefix('appointments')->controller(ConsultationController::class)->group(function () {
            Route::post('/{appointment}/consultations', 'storeConsultation');
        });
    });

    Route::middleware('role:doctor')->group(function () {
        Route::controller(DoctorVacationController::class)->prefix('doctor/vacations')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
        });

        Route::prefix('doctor/media')->group(function () {
            Route::post('/xrays', [PatientMediaController::class, 'uploadXray']);
            Route::post('/medical-tests', [PatientMediaController::class, 'uploadMedicalTests']);
        });

        Route::prefix('appointments')->controller(AppointmentController::class)->group(function () {
            Route::patch('/{appointment}/no-show', 'markNoShow');
            Route::patch('/{appointment}/cancel', 'cancelAsDoctor');
        });
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/overview', [AdminDashboardController::class, 'index']);

        Route::controller(App\Http\Controllers\Api\V1\Admin\DoctorController::class)->prefix('doctors')->group(function (
        ) {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{doctor}', 'show');
            Route::get('/{doctor}/agenda', 'agenda');
            Route::get('/{doctor}/reviews', 'reviews');
            Route::put('/{doctor}', 'update');
            Route::delete('/{doctor}', 'destroy');
        });

        Route::controller(App\Http\Controllers\Api\V1\Admin\PatientController::class)->prefix('patients')->group(function (
        ) {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{patient}', 'show');
            Route::get('/{patient}/invoices', 'invoices');
            Route::put('/{patient}', 'update');
            Route::delete('/{patient}', 'destroy');
        });

        Route::get('/invoices', [InvoiceController::class, 'index']);

        Route::post('/patients/{patient}/buy-package',
            [PatientPackageController::class, 'buyPackageForPatient']);

        Route::controller(PackageController::class)->prefix('packages')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{package}', 'show');
            Route::put('/{package}', 'update');
            Route::delete('/{package}', 'destroy');
        });

        Route::controller(App\Http\Controllers\Api\V1\Admin\AppointmentController::class)->prefix('appointments')->group(function (
        ) {
            Route::get('/', 'index');
            Route::delete('/{appointment}', 'destroy');
        });

        Route::controller(ScheduleController::class)->prefix('schedules')->group(function () {
            Route::get('/', 'index');
        });

        Route::controller(ScheduleController::class)
            ->prefix('doctors/{doctor}/schedules')
            ->group(function () {
                Route::get('/', 'show');
                Route::put('/', 'update');
            });

        Route::controller(AdminVacationController::class)->prefix('vacations')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::patch('/{vacation}/approve', 'approve');
            Route::patch('/{vacation}/decline', 'decline');
            Route::patch('/{vacation}/drop', 'drop');
        });

        Route::controller(SecretaryController::class)->prefix('secretaries')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{secretary}', 'show');
            Route::put('/{secretary}', 'update');
            Route::delete('/{secretary}', 'destroy');
        });
    });

    Route::middleware('role:secretary')->prefix('secretary')->group(function () {
        Route::post('/patients/login-as-patient', [AuthController::class, 'LoginAsPatientForSecretary']);

        Route::controller(App\Http\Controllers\Api\V1\Secretary\PatientController::class)->prefix('patients')->group(function (
        ) {
            Route::get('/', 'index');
            Route::post('', 'store');

            Route::get('/{patient}', 'show');
            Route::get('/{patient}/appointments', 'appointments');
            Route::get('/{patient}/invoices', 'invoices');
            Route::post('/{patient}/buy-package', 'buyPackage');
        });

        Route::get('/appointments/today',
            [App\Http\Controllers\Api\V1\Secretary\AppointmentController::class, 'today']);
    });

    Route::prefix('chat')->controller(ChatController::class)->group(function () {
        Route::post('/sendmessages', 'sendMessage');
        Route::get('/{receiverId}/getmessages', 'getMessages');
    });

    Route::prefix('ai-chat')->controller(AiChatController::class)->group(function () {
        Route::post('/send', 'sendMessage');
        Route::get('/history', 'getHistory');
    });

    Route::prefix('invoices')->controller(FinancialController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/wallet/charge', 'purchasePackage');
        Route::get('/{invoice}/download', 'downloadInvoice');
    });

    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/doctors/specializations', [DoctorController::class, 'specializations']);
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show']);
    Route::get('/doctors/{doctor}/available-days', [DoctorAvailabilityController::class, 'getAvailableDays']);
    Route::get('/appointments/available-slots', [AppointmentController::class, 'getAvailableSlots']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
