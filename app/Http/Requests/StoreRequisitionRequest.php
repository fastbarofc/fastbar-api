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
            'department_id'             => 'required|integer|exists:departments,id',
            'event_id'                  => 'nullable|integer|exists:events,id',
            'destination_department_id' => 'nullable|integer|exists:departments,id',
            'notes'                     => 'nullable|string|max:1000',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'        => 'required|integer|exists:products,id',
            'items.*.quantity'          => 'required|integer|min:1',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->event_id && !$this->destination_department_id) {
                $validator->errors()->add('event_id', 'Selecione um evento ou departamento destino.');
            }

            if ($this->destination_department_id && $this->destination_department_id == $this->department_id) {
                $validator->errors()->add('destination_department_id', 'O departamento destino não pode ser igual ao departamento de origem.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'department_id.required'           => 'O departamento de origem é obrigatório.',
            'department_id.exists'             => 'O departamento de origem selecionado não existe.',
            'event_id.exists'                  => 'O evento selecionado não existe.',
            'destination_department_id.exists' => 'O departamento destino selecionado não existe.',
            'items.required'                   => 'É obrigatório incluir pelo menos um produto.',
            'items.min'                        => 'É obrigatório incluir pelo menos um produto.',
            'items.*.product_id.required'      => 'O produto é obrigatório.',
            'items.*.product_id.exists'        => 'O produto selecionado não existe.',
            'items.*.quantity.required'        => 'A quantidade é obrigatória.',
            'items.*.quantity.integer'         => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min'             => 'A quantidade deve ser pelo menos 1.',
        ];
    }
}
