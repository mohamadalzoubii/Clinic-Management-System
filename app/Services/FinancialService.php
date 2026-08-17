<?php

namespace App\Services;

use App\Enums\Medical\InvoiceStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Admin;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FinancialService
{
    public function __construct(private readonly VacationService $vacationService) {}

    public function buyPackage(User $user, Package $package)
    {
        return $this->buyPackageForUser($user, $package);
    }

    public function buyPackageForUser(User $user, Package $package)
    {
        $this->ensureInvoiceSchemaSupportsWalletRecharge();

        return DB::transaction(function () use ($user, $package) {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $lockedUser->increment('wallet_balance', $package->balance_amount);

            $lockedUser = $lockedUser->fresh();

            $invoice = $this->createInvoice(
                user: $lockedUser,
                appointment: null,
                amount: (float) ($package->price > 0 ? $package->price : $package->balance_amount),
                entryType: 'wallet_recharge',
                status: InvoiceStatus::PAID,
            );

            return [
                'user' => $lockedUser,
                'invoice' => $invoice,
            ];

        });
    }

    private function ensureInvoiceSchemaSupportsWalletRecharge(): void
    {
        $column = DB::selectOne("SHOW COLUMNS FROM invoices WHERE Field = 'appointment_id'");

        if (! $column || (($column->Null ?? 'NO') === 'YES')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE invoices DROP FOREIGN KEY invoices_appointment_id_foreign');
        } catch (\Throwable $throwable) {
            // The FK may already be missing in some local databases.
        }

        DB::statement('ALTER TABLE invoices MODIFY appointment_id BIGINT UNSIGNED NULL');

        try {
            DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_appointment_id_foreign FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE CASCADE');
        } catch (\Throwable $throwable) {
            // If the constraint already exists or the storage engine differs, continue.
        }
    }

    private function createInvoice(
        User $user,
        ?Appointment $appointment,
        float $amount,
        string $entryType,
        InvoiceStatus $status
    ): Invoice {
        $payload = [
            'appointment_id' => $appointment?->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'invoice_number' => $this->generateInvoiceNumber($entryType, $appointment?->id ?? $user->id),
            'status' => $status,
            'paid_at' => now(),
        ];

        if (Schema::hasColumn('invoices', 'entry_type')) {
            $payload['entry_type'] = $entryType;
        }

        return Invoice::create($payload);
    }

    private function generateInvoiceNumber(string $entryType, int $referenceId): string
    {
        $prefix = strtoupper(Str::of($entryType)->replace('_', '-'));

        return 'INV-'.$prefix.'-'.now()->format('YmdHis').'-'.$referenceId.'-'.Str::upper(Str::random(4));

    }

    public function payForAppointmentAndCreateInvoice(Appointment $appointment)
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->with(['doctor.user', 'patient.user'])
                ->firstOrFail();

            // Prevent charging if the doctor is on vacation for the appointment date
            if ($this->vacationService->isBlockingDate($appointment->doctor->id, $appointment->appointment_date)) {
                throw new BusinessLogicException('Doctor is unavailable on the selected date.');
            }

            $patientUser = User::query()
                ->whereKey($appointment->patient->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $appointment->doctor->session_price;

            if ($patientUser->wallet_balance < $amount) {
                throw new BusinessLogicException('You dont have enough balance to pay for this appointment.');
            }

            $patientUser->decrement('wallet_balance', $amount);

            return $this->createInvoice(
                user: $patientUser,
                appointment: $appointment,
                amount: $amount,
                entryType: 'appointment_payment',
                status: InvoiceStatus::PAID,
            );

        });
    }

    public function refundForDoctorCancellation(Appointment $appointment)
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->with(['doctor.user', 'patient.user'])
                ->firstOrFail();

            $refundAmount = $this->sessionAmountForAppointment($appointment);
            $patientUser = User::query()
                ->whereKey($appointment->patient->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingRefundQuery = Invoice::query()
                ->where('appointment_id', $appointment->id)
                ->where('status', InvoiceStatus::REFUNDED)
                ->lockForUpdate();

            if (Schema::hasColumn('invoices', 'entry_type')) {
                $existingRefundQuery->where('entry_type', 'doctor_cancellation_refund');
            } else {
                $existingRefundQuery->where('amount', -$refundAmount);
            }

            $existingRefund = $existingRefundQuery->first();

            if (! $existingRefund) {
                $patientUser->increment('wallet_balance', $refundAmount);

                $existingRefund = $this->createInvoice(
                    user: $patientUser,
                    appointment: $appointment,
                    amount: -$refundAmount,
                    entryType: 'doctor_cancellation_refund',
                    status: InvoiceStatus::REFUNDED,
                );
            }

            return [
                'refund_invoice' => $existingRefund,
            ];
        });
    }

    private function sessionAmountForAppointment(Appointment $appointment): float
    {
        return round((float) $appointment->doctor->session_price, 2);
    }

    public function refundForPatientCancellation(Appointment $appointment)
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->with(['doctor.user', 'patient.user'])
                ->firstOrFail();

            $sessionAmount = $this->sessionAmountForAppointment($appointment);
            $refundAmount = round($sessionAmount * 0.5, 2);
            $remainingAmount = round($sessionAmount - $refundAmount, 2);
            $clinicShare = round($remainingAmount * 0.7, 2);
            $doctorShare = round($remainingAmount - $clinicShare, 2);

            $clinicUser = $this->resolveClinicUser();
            $doctorUser = User::query()
                ->whereKey($appointment->doctor->user_id)
                ->lockForUpdate()
                ->firstOrFail();
            $patientUser = User::query()
                ->whereKey($appointment->patient->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingRefundQuery = Invoice::query()
                ->where('appointment_id', $appointment->id)
                ->where('status', InvoiceStatus::REFUNDED)
                ->lockForUpdate();

            if (Schema::hasColumn('invoices', 'entry_type')) {
                $existingRefundQuery->where('entry_type', 'patient_cancellation_refund');
            } else {
                $existingRefundQuery->where('amount', -$refundAmount);
            }

            $existingRefund = $existingRefundQuery->first();

            $existingClinicPayoutQuery = Invoice::query()
                ->where('appointment_id', $appointment->id)
                ->where('user_id', $clinicUser->id)
                ->where('status', InvoiceStatus::PAYOUT_COMPLETED)
                ->lockForUpdate();

            $existingDoctorPayoutQuery = Invoice::query()
                ->where('appointment_id', $appointment->id)
                ->where('user_id', $doctorUser->id)
                ->where('status', InvoiceStatus::PAYOUT_COMPLETED)
                ->lockForUpdate();

            if (Schema::hasColumn('invoices', 'entry_type')) {
                $existingClinicPayoutQuery->where('entry_type', 'patient_cancellation_payout_clinic');
                $existingDoctorPayoutQuery->where('entry_type', 'patient_cancellation_payout_doctor');
            } else {
                $existingClinicPayoutQuery->where('amount', $clinicShare);
                $existingDoctorPayoutQuery->where('amount', $doctorShare);
            }

            $existingClinicPayout = $existingClinicPayoutQuery->first();
            $existingDoctorPayout = $existingDoctorPayoutQuery->first();

            if (! $existingRefund) {
                $patientUser->increment('wallet_balance', $refundAmount);

                $existingRefund = $this->createInvoice(
                    user: $patientUser,
                    appointment: $appointment,
                    amount: -$refundAmount,
                    entryType: 'patient_cancellation_refund',
                    status: InvoiceStatus::REFUNDED,
                );
            }

            if (! $existingClinicPayout) {
                $clinicUser->increment('wallet_balance', $clinicShare);

                $existingClinicPayout = $this->createInvoice(
                    user: $clinicUser,
                    appointment: $appointment,
                    amount: $clinicShare,
                    entryType: 'patient_cancellation_payout_clinic',
                    status: InvoiceStatus::PAYOUT_COMPLETED,
                );
            }

            if (! $existingDoctorPayout) {
                $doctorUser->increment('wallet_balance', $doctorShare);

                $existingDoctorPayout = $this->createInvoice(
                    user: $doctorUser,
                    appointment: $appointment,
                    amount: $doctorShare,
                    entryType: 'patient_cancellation_payout_doctor',
                    status: InvoiceStatus::PAYOUT_COMPLETED,
                );
            }

            return [
                'refund_invoice' => $existingRefund,
                'clinic_payout' => $existingClinicPayout,
                'doctor_payout' => $existingDoctorPayout,
            ];
        });
    }

    private function resolveClinicUser(): User
    {
        return Admin::query()
            ->with('user')
            ->firstOrFail()
            ->user;
    }

    public function payoutForCompletion70_30(Appointment $appointment): array
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->with(['doctor.user'])
                ->firstOrFail();

            $clinicUser = $this->resolveClinicUser();
            $doctorUser = User::query()
                ->whereKey($appointment->doctor->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingClinicPayout = Invoice::query()
                ->where('appointment_id', $appointment->id)
                ->where('user_id', $this->resolveClinicUser()->id)
                ->where('status', InvoiceStatus::PAYOUT_COMPLETED)
                ->lockForUpdate()
                ->first();

            $existingDoctorPayout = Invoice::query()
                ->where('appointment_id', $appointment->id)
                ->where('user_id', $doctorUser->id)
                ->where('status', InvoiceStatus::PAYOUT_COMPLETED)
                ->lockForUpdate()
                ->first();

            $amount = (float) $appointment->doctor->session_price;
            $clinicShare = round($amount * 0.70, 2);
            $doctorShare = round($amount - $clinicShare, 2);

            if (! $existingClinicPayout) {
                $clinicUser->increment('wallet_balance', $clinicShare);

                $existingClinicPayout = $this->createInvoice(
                    user: $clinicUser,
                    appointment: $appointment,
                    amount: $clinicShare,
                    entryType: 'completion_payout_clinic',
                    status: InvoiceStatus::PAYOUT_COMPLETED,
                );
            }

            if (! $existingDoctorPayout) {
                $doctorUser->increment('wallet_balance', $doctorShare);

                $existingDoctorPayout = $this->createInvoice(
                    user: $doctorUser,
                    appointment: $appointment,
                    amount: $doctorShare,
                    entryType: 'completion_payout_doctor',
                    status: InvoiceStatus::PAYOUT_COMPLETED,
                );
            }

            return [
                'clinic' => $existingClinicPayout,
                'doctor' => $existingDoctorPayout,
            ];
        });
    }

    public function refundForEmergencyOverride(Appointment $appointment)
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->with(['doctor.user', 'patient.user'])
                ->firstOrFail();

            $refundAmount = $this->sessionAmountForAppointment($appointment);
            $patientUser = User::query()
                ->whereKey($appointment->patient->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingRefundQuery = Invoice::query()
                ->where('appointment_id', $appointment->id)
                ->where('status', InvoiceStatus::REFUNDED)
                ->lockForUpdate();

            if (Schema::hasColumn('invoices', 'entry_type')) {
                $existingRefundQuery->where('entry_type', 'emergency_override_refund');
            } else {
                $existingRefundQuery->where('amount', -$refundAmount);
            }

            $existingRefund = $existingRefundQuery->first();

            if (! $existingRefund) {
                $patientUser->increment('wallet_balance', $refundAmount);

                $existingRefund = $this->createInvoice(
                    user: $patientUser,
                    appointment: $appointment,
                    amount: -$refundAmount,
                    entryType: 'emergency_override_refund',
                    status: InvoiceStatus::REFUNDED,
                );
            }

            return [
                'refund_invoice' => $existingRefund,
            ];
        });
    }
}
