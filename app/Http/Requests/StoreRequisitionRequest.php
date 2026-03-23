<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequisitionRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => 'required|integer|exists:events,id',
            'department_id' => 'required|integer|exists:departments,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'event_id.required' => 'O evento é obrigatório.',
            'event_id.exists' => 'O evento selecionado não existe.',
            'department_id.required' => 'O departamento é obrigatório.',
            'department_id.exists' => 'O departamento selecionado não existe.',
            'items.required' => 'É obrigatório incluir pelo menos um produto.',
            'items.min' => 'É obrigatório incluir pelo menos um produto.',
            'items.*.product_id.required' => 'O produto é obrigatório.',
            'items.*.product_id.exists' => 'O produto selecionado não existe.',
            'items.*.quantity.required' => 'A quantidade é obrigatória.',
            'items.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
        ];
    }
}
