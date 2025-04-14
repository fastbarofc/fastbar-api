<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollaboratorGoalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'in:monthly,yearly',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'expected_contracts' => 'integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'O campo tipo deve ser "monthly" ou "yearly".',
            'start_date.date' => 'O campo data de início deve ser uma data válida.',
            'end_date.date' => 'O campo data de término deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
            'expected_contracts.integer' => 'O campo número de contratos previstos deve ser um número inteiro.',
        ];
    }
}
