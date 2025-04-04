<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayableRequest extends FormRequest
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
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'financial_operation_id' => 'required|exists:financial_operations,id',
            'payableable_id' => 'required|integer',
            'payableable_type' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'A descrição é obrigatória.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'due_date.required' => 'A data de vencimento é obrigatória.',
            'due_date.date' => 'A data de vencimento deve estar em um formato válido.',
            'financial_operation_id.required' => 'A operação financeira é obrigatória.',
            'financial_operation_id.exists' => 'A operação financeira informada não existe.',
            'payableable_id.required' => 'O ID do relacionamento polimórfico é obrigatório.',
            'payableable_type.required' => 'O tipo do relacionamento polimórfico é obrigatório.',
        ];
    }
}
