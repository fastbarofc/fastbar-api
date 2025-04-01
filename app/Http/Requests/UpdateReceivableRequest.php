<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReceivableRequest extends FormRequest
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
            'payment_method_id' => 'sometimes|exists:payment_methods,id',
            'receivableable_id' => 'sometimes|integer',
            'receivableable_type' => 'sometimes|string',
        ];
    }
}
