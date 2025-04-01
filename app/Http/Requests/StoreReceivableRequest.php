<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceivableRequest extends FormRequest
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
            'payment_method_id' => 'required|exists:payment_methods,id',
            'receivableable_id' => 'required|integer',
            'receivableable_type' => 'required|string',

        ];
    }

    public function messages(): array
    {
        return[
            'description.required' => 'A descrição é obrigatória.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'due_date.required' => 'A data de vencimento é obrigatória.',
            'due_date.date' => 'A data de vencimento deve estar em um formato válido.',
            'payment_method_id.required' => 'O método de pagamento é obrigatório.',
            'payment_method_id.exists' => 'O método de pagamento informado não existe.',
            'receivableable_id.required' => 'O ID do relacionamento polimórfico é obrigatório.',
            'receivableable_type.required' => 'O tipo do relacionamento polimórfico é obrigatório.',
        ];
    }
}
