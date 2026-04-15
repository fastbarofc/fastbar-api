<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $stockId = $this->route('stock')->id ?? null;

        return [
            'name' => 'nullable|string|max:255|unique:stocks,name,' . $stockId,
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'O campo nome deve ser um texto.',
            'name.max' => 'O campo nome não pode ter mais de 255 caracteres.',
            'name.unique' => 'Já existe um estoque com este nome.',
        ];
    }
}
