<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => 'required|string|max:255|unique:products,name',
            'description'         => 'required|string|max:255',
            'group'               => 'nullable|string|max:255',
            'barcode'             => 'nullable|string|max:255',
            'unit_id'             => 'required|numeric|exists:units,id',
            'weight'              => 'nullable|numeric',
            'manufatturer'        => 'nullable|string|max:255',
            'value'               => 'required|numeric|min:0',
            'supplier'            => 'nullable|string|max:255',
            'marketable'          => 'required|boolean',
            'average_consumption' => 'nullable|numeric',
            'display_checklist'   => 'required|boolean',
            'own_production'      => 'required|boolean',
            'recipe_yield'        => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'               => 'O campo nome é obrigatório.',
            'name.string'                 => 'O campo nome deve ser uma string.',
            'name.max'                    => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'                 => 'O nome informado já está em uso.',
            'description.required'        => 'O campo descrição é obrigatório.',
            'description.string'          => 'O campo descrição deve ser uma string.',
            'description.max'             => 'O campo descrição deve ter no máximo 255 caracteres.',
            'group.string'                => 'O campo grupo deve ser uma string.',
            'group.max'                   => 'O campo grupo deve ter no máximo 255 caracteres.',
            'barcode.string'              => 'O campo código de barra deve ser uma string.',
            'barcode.max'                 => 'O campo código de barra deve ter no máximo 255 caracteres.',
            'unit_id.required'            => 'O campo unidade é obrigatório.',
            'unit_id.numeric'             => 'O campo ID da unidade deve conter apenas números.',
            'unit_id.exists'              => 'O campo ID da unidade deve ser uma unidade válida',
            'weight.numeric'              => 'O campo peso deve ser uma string.',
            'manufatturer.string'         => 'O campo fabricante deve ser uma string.',
            'manufatturer.max'            => 'O campo fabricante deve ter no máximo 255 caracteres.',
            'value.required'              => 'O campo valor é obrigatório.',
            'value.numeric'               => 'O campo valor deve ser numérico.',
            'value.min'                   => 'O campo valor não pode ser negativo',
            'supplier.string'             => 'O campo fornecedor deve ser uma string.',
            'supplier.max'                => 'O campo fornecedor deve ter no máximo 255 caracteres.',
            'marketable.required'         => 'O campo comercializável é obrigatório.',
            'marketable.boolean'          => 'O campo comercializável deve ser um formato válido.',
            'average_consumption.numeric' => 'O campo consumo médio deve conter apenas números.',
            'display_checklist.required'  => 'O campo checklist é obrigatório.',
            'display_checklist.boolean'   => 'O campo checklist deve estar em formato válido.',
            'own_production.required'     => 'O campo produção própria é obrigatório.',
            'own_production.boolean'      => 'O campo produção própria estar em formato válido.',
            'recipe_yield.numeric'        => 'O campo rendimento deve conter apenas números.',
        ];
    }
}
