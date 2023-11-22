<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectorRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:sectors,name'
        ];
    }


    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório',
            'name.string'   => 'O campo nome deve ser uma string',
            'name.max'      => 'O campo nome deve ter no máximo 255 caracteres',
            'name.unique'   => 'O nome informado já está em uso'
        ];
    }
}