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

    public function messages(): array
    {
        return [
            'description.string' => 'A descrição deve ser um texto.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'amount.min' => 'O valor não pode ser negativo.',
            'due_date.date' => 'A data de vencimento deve estar em um formato válido.',
            'paid_at.date' => 'A data de pagamento deve estar em um formato válido.',
            'status.in' => 'O status deve ser pending, paid ou overdue.',
            'financial_operation_id.exists' => 'A operação financeira informada não existe.',
            'payableable_id.integer' => 'O ID do relacionamento polimórfico deve ser um número inteiro.',
            'payableable_type.string' => 'O tipo do relacionamento polimórfico deve ser um texto.',
        ];
    }
}
