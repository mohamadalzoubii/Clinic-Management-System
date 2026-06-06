<?php

namespace App\Http\Requests\Admin;

use App\Enums\Medical\DayOfWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slot_duration' => ['required', 'integer', 'min:1', 'max:480'],
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day_of_week' => ['required', 'string', Rule::in($this->dayOrder())],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i'],
        ];
    }

    protected function passedValidation(): void
    {
        $scheduleErrors = [];

        foreach ($this->input('schedules') as $index => $scheduleData) {
            if ($scheduleData['end_time'] <= $scheduleData['start_time']) {
                $scheduleErrors["schedules.$index.end_time"] = [
                    'End time must be after start time for '.strtoupper($scheduleData['day_of_week']).'.',
                ];
            }
        }

        if (! empty($scheduleErrors)) {
            throw ValidationException::withMessages($scheduleErrors);
        }
    }

    private function dayOrder(): array
    {
        return array_map(fn (DayOfWeek $day) => $day->value, DayOfWeek::cases());
    }
}
