<?php

namespace App\Http\Requests;

use App\Models\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class UpdateMaterialRequest extends FormRequest
{
    protected $stopOnFirtsFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:255|unique:materials,description,' . $this->route('material')->id,
            'quantity'      => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'description.string' => 'O campo descrição deve ser uma string.',
            'description.max'    => 'O campo descrição deve ter no máximo 255 caracteres.',
            'description.unique' => 'Essa descrição já está sendo utiliada',
            'quantity.numeric'     => 'O campo quantidade deve ser conter apenas números.',
            'quantity.min'         => 'O campo quantidade não pode ser negativo',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $newQuantity = $this->input('quantity');

            if ($newQuantity === null) {
                return;
            }

            $material = $this->route('material');
            $oldQuantity = $material->quantity;
            $newQuantity = (int) $newQuantity;

            if ($newQuantity >= $oldQuantity) {
                return;
            }

            $inactiveStatusNames = ['Concluído', 'Descartado', 'Cancelado', 'Fechou com o concorrente'];
            $inactiveStatusIds = EventStatus::whereIn('name', $inactiveStatusNames)->pluck('id')->toArray();

            $totalUsedQuantity = DB::table('event_material')
                ->join('events', 'event_material.event_id', '=', 'events.id')
                ->where('event_material.material_id', $material->id)
                ->whereNotIn('events.event_status_id', $inactiveStatusIds)
                ->whereNull('events.deleted_at')
                ->whereNull('event_material.deleted_at')
                ->sum('event_material.quantity');

            $totalUsedQuantity = (int) $totalUsedQuantity;

            if ($totalUsedQuantity > $newQuantity) {
                $validator->errors()->add(
                    'quantity',
                    "Não é possível diminuir a quantidade para {$newQuantity}. Existem eventos ativos utilizando {$totalUsedQuantity} unidade(s) deste material."
                );
            }
        });
    }
}
