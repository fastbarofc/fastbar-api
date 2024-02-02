<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectorOfficeRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255|unique:offices,name,' . $this->route('office')->id,
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'   => 'O campo nome deve ser um texto.',
            'name.max'      => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'   => 'O nome informado já está em uso.',
        ];
    }
}