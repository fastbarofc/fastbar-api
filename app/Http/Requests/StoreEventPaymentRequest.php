<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventPaymentRequest extends FormRequest
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
            'event_id' => 'required|integer|exists:events,id',
            'parthenon_bank_account_id' => 'required|integer|exists:parthenon_bank_accounts,id',
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'value' => 'required|numeric|min:0|max:99999999.99',
            'date' => 'required|date_format:Y-m-d',
            'comment' => 'nullable|string|max:65535',
        ];
    }

    public function messages(): array
    {
        return [
            'event_id.required' => 'Informe o evento.',
            'event_id.exists' => 'O evento informado não existe.',
            'parthenon_bank_account_id.required' => 'Informe a conta bancária.',
            'parthenon_bank_account_id.exists' => 'A conta bancária informada não existe.',
            'payment_method_id.required' => 'Informe o método de pagamento.',
            'payment_method_id.exists' => 'O método de pagamento informado não existe.',
            'value.required' => 'Informe o valor do pagamento.',
            'value' => 'O valor do pagamento deve estar entre 0 e 99.999.999,99.',
            'date.required' => 'Informe a data do pagamento.',
            'date' => 'A data do pagamento é inválida.',
            'comment' => 'O comentário é longo demais.',
        ];
    }
}
