<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventCaptureRequest extends FormRequest
{
    protected $stopOnFailure = true;

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
            'name' => 'required|string|max:255|unique:event_captures',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'O campo nome é obrigatório.',
            'name.string'        => 'O campo nome deve ser um texto.',
            'name.max'           => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'        => 'O nome informado já está em uso.',
        ];
    }
}
