<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => 'nullable|string|max:255|unique:products,name,' . $this->route('product')->id,
            'group'               => 'nullable|string|max:255',
            'barcode'             => 'nullable|string|max:255',
            'unit_id'             => 'nullable|numeric|exists:units,id',
            'weight'              => 'nullable|numeric',
            'manufacturer'        => 'nullable|string|max:255',
            'value'               => 'nullable|numeric|min:0|max:9999999',
            'supplier'            => 'nullable|string|max:255',
            'marketable'          => 'nullable|boolean',
            'average_consumption' => 'nullable|numeric|max:999999',
            'display_checklist'   => 'nullable|boolean',
            'own_production'      => 'nullable|boolean',
            'recipe_yield'        => 'nullable|numeric|max:999999',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'                 => 'O campo nome deve ser uma string.',
            'name.max'                    => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'                 => 'O nome informado já está em uso.',
            'group.string'                => 'O campo grupo deve ser uma string.',
            'group.max'                   => 'O campo grupo deve ter no máximo 255 caracteres.',
            'barcode.string'              => 'O campo código de barra deve ser uma string.',
            'barcode.max'                 => 'O campo código de barra deve ter no máximo 255 caracteres.',
            'unit_id.numeric'             => 'O campo ID da unidade deve conter apenas números.',
            'unit_id.exists'              => 'O campo ID da unidade deve ser uma unidade válida',
            'weight.numeric'              => 'O campo peso deve ser uma string.',
            'manufacturer.string'         => 'O campo fabricante deve ser uma string.',
            'manufacturer.max'            => 'O campo fabricante deve ter no máximo 255 caracteres.',
            'value.numeric'               => 'O campo valor deve ser numérico.',
            'value.min'                   => 'O campo valor não pode ser negativo',
            'value.max'                   => 'O valor do campo "Valor" não pode ser superior a 9.999.999.',
            'supplier.string'             => 'O campo fornecedor deve ser uma string.',
            'supplier.max'                => 'O campo fornecedor deve ter no máximo 255 caracteres.',
            'average_consumption.numeric' => 'O campo consumo médio deve conter apenas números.',
            'average_consumption.max'     => 'O campo consumo médio não pode ser superior a 999.999.',
            'marketable.boolean'          => 'O campo comercializável deve ser um booleano.',
            'display_checklist.boolean'   => 'O campo display deve ser um booleano.',
            'own_production.boolean'      => 'O campo produção própria deve ser um booleano.',
            'recipe_yield.numeric'        => 'O campo rendimento deve conter apenas números.',
            'recipe_yield.max'            => 'O campo rendimento não pode ser superior a 999.999.',
        ];
    }
}
