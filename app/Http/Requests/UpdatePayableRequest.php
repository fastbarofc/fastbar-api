<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayableRequest extends FormRequest
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
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date',
            'financial_operation_id' => 'sometimes|exists:financial_operations,id',
            'payableable_id' => 'sometimes|integer',
            'payableable_type' => 'sometimes|string',
        ];
    }
}
