<?php

namespace App\Http\Requests;

use App\Models\EventAddendum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventAddendumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'types' => 'required_without:notes|array',
            'types.*' => ['distinct', Rule::in(array_keys(EventAddendum::TYPES))],
            'notes' => 'nullable|string|max:2000',
            'payment_terms' => 'nullable|string|max:1000',

            'unit_price' => [Rule::requiredIf($this->hasType('unit_price')), 'nullable', 'numeric', 'min:0.01'],

            'guests_added' => [Rule::requiredIf($this->hasType('guests')), 'nullable', 'integer', 'min:1'],

            'menu_modality' => [Rule::requiredIf($this->hasType('menu')), 'nullable', 'string', 'max:255'],
            'menu_remove' => 'nullable|array',
            'menu_remove.*.event_kit_id' => 'required|integer',
            'menu_remove.*.product_id' => 'required|integer',
            'menu_add' => 'nullable|array',
            'menu_add.*.kit_id' => 'required|integer|exists:kits,id',
            'menu_add.*.product_id' => 'nullable|integer|exists:products,id',

            'value_added' => [Rule::requiredIf($this->hasType('value')), 'nullable', 'numeric', 'min:0.01'],
            'value_description' => 'nullable|string|max:255',

            'services' => [Rule::requiredIf($this->hasType('service')), 'nullable', 'array'],
            'services.*.description' => 'required|string|max:255',
            'services.*.value' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'types.required_without' => 'Escolha ao menos um tipo de alteração ou descreva a alteração em Outras alterações.',
            'unit_price.required' => 'Informe o novo valor por convidado.',
            'unit_price.min' => 'O novo valor por convidado deve ser maior que zero.',
            'guests_added.required' => 'Informe quantos convidados serão acrescentados.',
            'guests_added.min' => 'Acrescente ao menos um convidado.',
            'menu_modality.required' => 'Informe a modalidade do novo cardápio.',
            'value_added.required' => 'Informe o valor a ser acrescentado.',
            'value_added.min' => 'O valor acrescentado deve ser maior que zero.',
            'services.required' => 'Adicione ao menos um serviço.',
            'services.*.description.required' => 'Descreva cada serviço adicional.',
        ];
    }

    private function hasType(string $type): bool
    {
        return in_array($type, (array) $this->input('types', []), true);
    }
}
