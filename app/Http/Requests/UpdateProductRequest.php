<?php

namespace App\Http\Requests;

use App\Models\ProductGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'product_group_id'    => 'nullable|numeric|exists:product_groups,id',
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
            'composition_item'    => 'nullable|boolean',
            'composition'              => 'nullable|array',
            'composition.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::notIn([$this->route('product')->id]),
                Rule::exists('products', 'id')->where('composition_item', true)->whereNull('deleted_at'),
            ],
            'composition.*.quantity'   => 'required|numeric|gt:0|max:999999',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'                      => 'O campo nome deve ser uma string.',
            'name.max'                         => 'O campo nome deve ter no máximo 255 caracteres.',
            'name.unique'                      => 'O nome informado já está em uso.',
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
            'average_consumption.numeric' => 'O campo consumo médio deve conter apenas números.',
            'average_consumption.max'     => 'O campo consumo médio não pode ser superior a 999.999.',
            'marketable.boolean'          => 'O campo comercializável deve ser um booleano.',
            'display_checklist.boolean'   => 'O campo display deve ser um booleano.',
            'own_production.boolean'      => 'O campo produção própria deve ser um booleano.',
            'recipe_yield.numeric'        => 'O campo rendimento deve conter apenas números.',
            'recipe_yield.max'            => 'O campo rendimento não pode ser superior a 999.999.',
            'composition_item.boolean'    => 'O campo item de composição deve estar em formato válido.',
            'composition.array'                   => 'A composição deve ser uma lista de itens.',
            'composition.*.product_id.required'   => 'Informe o item da composição.',
            'composition.*.product_id.integer'    => 'O item da composição é inválido.',
            'composition.*.product_id.distinct'   => 'Um item não pode aparecer duas vezes na composição.',
            'composition.*.product_id.not_in'     => 'Um produto não pode compor a si mesmo.',
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
            $product = $this->route('product');
            $isCompositionItem = $this->has('composition_item')
                ? $this->boolean('composition_item')
                : $product->composition_item;

            if ($isCompositionItem) {
                $hasComposition = $this->has('composition')
                    ? !empty($this->input('composition'))
                    : $product->components()->exists();

                if ($hasComposition) {
                    $validator->errors()->add('composition', 'Item de composição não pode ter outros produtos na sua composição. Remova os itens da composição antes.');
                }

                return;
            }

            $composedProducts = $product->composedProducts()->pluck('name');

            if ($composedProducts->isNotEmpty()) {
                $validator->errors()->add(
                    'composition_item',
                    'Este produto faz parte da composição de: ' . $composedProducts->join(', ') . '. Remova-o dessas composições antes de desmarcá-lo como item de composição.'
                );
            }
        });
    }
}
