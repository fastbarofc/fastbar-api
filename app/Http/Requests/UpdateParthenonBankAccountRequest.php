<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParthenonBankAccountRequest extends FormRequest
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
            'bank_name' => 'string|max:255',
            'account_number' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'account_kind' => 'nullable|in:banco,caixa,cartao_credito',
            'agency' => 'nullable|string|max:30',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date',
            'card_brand' => 'nullable|string|max:40',
            'card_last_digits' => 'nullable|string|max:8',
            'closing_day' => 'nullable|integer|min:1|max:31',
            'due_day' => 'nullable|integer|min:1|max:31',
            'active' => 'nullable|boolean',
        ];
    }
}
