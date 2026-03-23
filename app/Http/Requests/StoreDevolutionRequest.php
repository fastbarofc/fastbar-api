<?php

namespace App\Http\Requests;

use App\Models\Requisition;
use Illuminate\Foundation\Http\FormRequest;

class StoreDevolutionRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requisition_id' => 'required|integer|exists:requisitions,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity_returned' => 'required|integer|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $requisitionId = $this->input('requisition_id');
            if (!$requisitionId) return;

            $requisition = Requisition::with('requisitionItems')->find($requisitionId);
            if (!$requisition) return;

            if ($requisition->devolution) {
                $validator->errors()->add('requisition_id', 'Esta requisição já possui uma devolução registrada.');
                return;
            }

            $requisitionProductIds = $requisition->requisitionItems->pluck('product_id')->sort()->values()->toArray();
            $itemProductIds = collect($this->input('items', []))->pluck('product_id')->sort()->values()->toArray();

            if ($requisitionProductIds !== $itemProductIds) {
                $validator->errors()->add('items', 'Todos os produtos da requisição devem ter devolução informada.');
            }

            foreach ($this->input('items', []) as $index => $item) {
                $requisitionItem = $requisition->requisitionItems->where('product_id', $item['product_id'])->first();
                if ($requisitionItem && $item['quantity_returned'] > $requisitionItem->quantity) {
                    $validator->errors()->add(
                        "items.{$index}.quantity_returned",
                        "A quantidade devolvida não pode ser maior que a quantidade requisitada ({$requisitionItem->quantity})."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'requisition_id.required' => 'A requisição é obrigatória.',
            'requisition_id.exists' => 'A requisição selecionada não existe.',
            'items.required' => 'É obrigatório informar os itens da devolução.',
            'items.min' => 'É obrigatório informar pelo menos um item.',
            'items.*.product_id.required' => 'O produto é obrigatório.',
            'items.*.product_id.exists' => 'O produto selecionado não existe.',
            'items.*.quantity_returned.required' => 'A quantidade devolvida é obrigatória.',
            'items.*.quantity_returned.integer' => 'A quantidade devolvida deve ser um número inteiro.',
            'items.*.quantity_returned.min' => 'A quantidade devolvida deve ser pelo menos 0.',
        ];
    }
}
