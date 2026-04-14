<?php

namespace App\Http\Requests;

use App\Models\ProductGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'product_group_id'    => 'required|numeric|exists:product_groups,id',
            'product_subgroup_id' => [
                Rule::requiredIf(function () {
                    $groupId = $this->input('product_group_id');
                    if (!$groupId) return false;
                    return ProductGroup::find($groupId)?->subgroups()->exists() ?? false;
                }),
                'nullable',
                'numeric',
                Rule::exists('product_subgroups', 'id')->where('product_group_id', $this->input('product_group_id')),
            ],
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
            'name.required'                    => 'O campo nome é obrigatório.',
            'name.string'                      => 'O campo nome deve ser uma string.',
            'name.max'                         => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'                      => 'O nome informado já está em uso.',
            'product_group_id.required'        => 'O campo grupo é obrigatório.',
            'product_group_id.numeric'         => 'O campo grupo deve ser numérico.',
            'product_group_id.exists'          => 'O grupo informado não existe.',
            'product_subgroup_id.required'     => 'O subgrupo é obrigatório para o grupo selecionado.',
            'product_subgroup_id.numeric'      => 'O campo subgrupo deve ser numérico.',
            'product_subgroup_id.exists'       => 'O subgrupo informado não pertence ao grupo selecionado.',
            'barcode.string'              => 'O campo código de barra deve ser uma string.',
            'barcode.max'                 => 'O campo código de barra deve ter no máximo 255 caracteres.',
            'unit_id.numeric'             => 'O campo ID da unidade deve conter apenas números.',
            'unit_id.exists'              => 'O campo ID da unidade deve ser uma unidade válida',
            'weight.numeric'              => 'O campo peso deve ser uma string.',
            'manufacturer.string'         => 'O campo fabricante deve ser uma string.',
            'manufacturer.max'            => 'O campo fabricante deve ter no máximo 255 caracteres.',
            'value.numeric'               => 'O campo valor deve ser numérico.',
            'value.min'                   => 'O campo valor não pode ser negativo',
            'value.max'                   => 'O campo valor não pode ser superior a 9.999.999.',
            'supplier.string'             => 'O campo fornecedor deve ser uma string.',
            'supplier.max'                => 'O campo fornecedor deve ter no máximo 255 caracteres.',
            'marketable.boolean'          => 'O campo comercializável deve ser um formato válido.',
            'average_consumption.numeric' => 'O campo consumo médio deve conter apenas números.',
            'average_consumption.max'     => 'O campo consumo médio não pode ser superior a 999.999.',
            'display_checklist.boolean'   => 'O campo checklist deve estar em formato válido.',
            'own_production.boolean'      => 'O campo produção própria estar em formato válido.',
            'recipe_yield.numeric'        => 'O campo rendimento deve conter apenas números.',
            'recipe_yield.max'            => 'O campo rendimento não pode ser superior a 999.999.',
        ];
    }
}
