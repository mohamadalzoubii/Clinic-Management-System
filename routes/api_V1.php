<?php

use App\Http\Controllers\Api\V1\AiChatController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\ConsultationController;
use App\Http\Controllers\Api\V1\DoctorAvailabilityController;
use App\Http\Controllers\Api\V1\FinancialController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verify']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/doctors/login', [AuthController::class, 'DoctorLogin']);

Route::middleware('auth:sanctum')->group(function () {

    Route::middleware('role:patient')->group(function () {

        Route::prefix('patient')->controller(PatientController::class)->group(function () {
            Route::post('/CompleteProftile', 'completeProftile');
            Route::put('updateprofile/{patient}', 'updateProfile');

        });

        Route::prefix('doctors')->controller(DoctorController::class)->group(function () {
            Route::post('/{doctor}/reviews', 'storeReview');
        });

        Route::prefix('appointments')->controller(AppointmentController::class)->group(function () {
            Route::post('/storeAppointment', 'store');
            Route::patch('/{appointment}/update', 'cansal');
        });
    });

    Route::middleware('role:doctor')->group(function () {

        Route::get('/doctors/dashboard', [DoctorController::class, 'dashboard']);
        Route::get('/patients', [PatientController::class, 'index']);
        Route::get('/patients/{patient}', [PatientController::class, 'show']);

        Route::get('/available-days',
            [DoctorAvailabilityController::class, 'getAvailableDays']);

        Route::get('/available-slots',
            [DoctorAvailabilityController::class, 'getAvailableSlots']);

        Route::get('/doctor/agenda', [DoctorAvailabilityController::class, 'getAgenda']);

        Route::prefix('appointments')->controller(ConsultationController::class)->group(function () {
            Route::post('/{appointment}/consultations', 'storeConsultation');

        });
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

        Route::post('/wallet/charge', 'purchasePackage');

        Route::get('/{invoice}/download', 'downloadInvoice');

    });

    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show']);

    Route::get('/doctors/{doctor}/available-days', [DoctorAvailabilityController::class, 'getAvailableDays']);
    Route::get('/appointments/available-slots', [AppointmentController::class, 'getAvailableSlots']);

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);

    Route::post('/logout', [AuthController::class, 'logout']);

});
