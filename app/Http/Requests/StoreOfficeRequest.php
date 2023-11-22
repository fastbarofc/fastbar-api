<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfficeRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sector_id' => 'required|exists:sectors,id',
            'name'      => 'required|string|max:255|unique:offices',
        ];
    }

    public function messages(): array
    {
        return [
            'sector_id.required' => 'O campo setor é obrigatório.',
            'sector_id.exists'   => 'O setor informado não existe.',
            'name.required'      => 'O campo nome é obrigatório.',
            'name.string'        => 'O campo nome deve ser um texto.',
            'name.max'           => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'        => 'O nome informado já está em uso.',
        ];
    }
}