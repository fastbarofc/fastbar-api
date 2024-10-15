<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:events,id'
        ];
    }

    public function messages() 
    {
        return [
            'id.required' => 'O campo ID do evento é obrigatório.',
            'id.exists' => 'O ID evento deve ser um ID válido',
        ];
    }
}
