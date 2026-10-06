<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IndexCalendarEventRequest extends FormRequest
{
    // O calendario pede no maximo um mes com uma semana de folga de cada lado.
    public const MAX_RANGE_DAYS = 62;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start' => 'nullable|required_with:end|date',
            'end' => 'nullable|required_with:start|date|after_or_equal:start',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || !$this->filled('start')) {
                return;
            }

            $start = Carbon::parse($this->input('start'))->startOfDay();
            $end = Carbon::parse($this->input('end'))->startOfDay();

            if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
                $validator->errors()->add('end', 'O período deve ter no máximo ' . self::MAX_RANGE_DAYS . ' dias.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'start.required_with' => 'O campo início é obrigatório quando o fim é informado.',
            'start.date' => 'O campo início deve ser uma data válida.',
            'end.required_with' => 'O campo fim é obrigatório quando o início é informado.',
            'end.date' => 'O campo fim deve ser uma data válida.',
            'end.after_or_equal' => 'O campo fim deve ser uma data igual ou posterior ao início.',
        ];
    }
}
