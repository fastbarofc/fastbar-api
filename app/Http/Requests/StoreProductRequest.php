<?php

namespace App\Http\Requests;

use App\Models\ProductGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'volume'              => 'nullable|numeric|min:0|max:9999999',
            'value'               => 'nullable|numeric|min:0|max:9999999',
            'supplier'            => 'nullable|string|max:255',
            'marketable'          => 'nullable|boolean',
            'consumption_per_guest' => 'nullable|numeric|min:0|max:99999',
            'display_checklist'   => 'nullable|boolean',
            'own_production'      => 'nullable|boolean',
            'recipe_yield'        => 'nullable|numeric|max:999999',
            'composition_item'    => 'nullable|boolean',
            'composition'              => 'nullable|array',
            'composition.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where('composition_item', true)->whereNull('deleted_at'),
            ],
            'composition.*.quantity'   => 'required|numeric|gt:0|max:999999',
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
            'volume.numeric'              => 'O campo volume deve conter apenas números.',
            'volume.min'                  => 'O campo volume não pode ser negativo.',
            'volume.max'                  => 'O campo volume não pode ser superior a 9.999.999.',
            'value.numeric'               => 'O campo valor deve ser numérico.',
            'value.min'                   => 'O campo valor não pode ser negativo',
            'value.max'                   => 'O campo valor não pode ser superior a 9.999.999.',
            'supplier.string'             => 'O campo fornecedor deve ser uma string.',
            'supplier.max'                => 'O campo fornecedor deve ter no máximo 255 caracteres.',
            'marketable.boolean'          => 'O campo comercializável deve ser um formato válido.',
            'consumption_per_guest.numeric' => 'O campo consumo por convidado deve conter apenas números.',
            'consumption_per_guest.min'     => 'O campo consumo por convidado não pode ser negativo.',
            'consumption_per_guest.max'     => 'O campo consumo por convidado não pode ser superior a 99.999.',
            'display_checklist.boolean'   => 'O campo checklist deve estar em formato válido.',
            'own_production.boolean'      => 'O campo produção própria estar em formato válido.',
            'recipe_yield.numeric'        => 'O campo rendimento deve conter apenas números.',
            'recipe_yield.max'            => 'O campo rendimento não pode ser superior a 999.999.',
            'composition_item.boolean'    => 'O campo item de composição deve estar em formato válido.',
            'composition.array'                   => 'A composição deve ser uma lista de itens.',
            'composition.*.product_id.required'   => 'Informe o item da composição.',
            'composition.*.product_id.integer'    => 'O item da composição é inválido.',
            'composition.*.product_id.distinct'   => 'Um item não pode aparecer duas vezes na composição.',
            'composition.*.product_id.exists'     => 'Só produtos marcados como item de composição podem compor outro produto.',
            'composition.*.quantity.required'     => 'Informe a quantidade de cada item da composição.',
            'composition.*.quantity.numeric'      => 'A quantidade do item da composição deve ser numérica.',
            'composition.*.quantity.gt'           => 'A quantidade do item da composição deve ser maior que zero.',
            'composition.*.quantity.max'          => 'A quantidade do item da composição não pode ser superior a 999.999.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->boolean('composition_item') && !empty($this->input('composition'))) {
                $validator->errors()->add('composition', 'Item de composição não pode ter outros produtos na sua composição.');
            }
        });
    }
}
