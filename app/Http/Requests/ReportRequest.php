<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    private const ID_LISTS = [
        'status_ids',
        'state_ids',
        'city_ids',
        'client_ids',
        'event_place_ids',
        'event_type_ids',
        'collaborator_ids',
        'partner_ids',
        'event_capture_ids',
        'how_did_you_hear_ids',
        'lost_reason_ids',
        'competitor_ids',
        'payment_method_ids',
        'bank_account_ids',
    ];

    public function authorize(): bool
    {
        return true;
    }

    // O front envia as listas como "1,2,3" para manter a URL do relatório legível.
    protected function prepareForValidation(): void
    {
        foreach (self::ID_LISTS as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => array_values(array_filter(explode(',', $this->input($key)), 'strlen'))]);
            }
        }
    }

    public function rules(): array
    {
        $rules = [
            'period_start' => ['nullable', 'date_format:Y-m-d'],
            'period_end' => ['nullable', 'date_format:Y-m-d'],
            'date_field' => ['nullable', Rule::in(['date', 'closed_at', 'created_at'])],
            'group_by' => ['nullable', 'string'],
            'order_by' => ['nullable', 'string'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'search' => ['nullable', 'string', 'max:100'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'min_guests' => ['nullable', 'integer'],
            'max_guests' => ['nullable', 'integer'],
            'financial_situation' => ['nullable', Rule::in(['paid_off', 'with_balance', 'no_payment'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];

        foreach (self::ID_LISTS as $key) {
            $rules[$key] = ['nullable', 'array'];
            $rules["{$key}.*"] = ['integer'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'date_format' => 'O campo :attribute deve ser uma data válida.',
            'numeric' => 'O campo :attribute deve ser um número.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'in' => 'O valor informado em :attribute não é válido.',
            'array' => 'O campo :attribute deve ser uma lista.',
            'max' => 'O campo :attribute deve ter no máximo :max.',
            'min' => 'O campo :attribute deve ser no mínimo :min.',
        ];
    }

    public function attributes(): array
    {
        return [
            'period_start' => 'início do período',
            'period_end' => 'fim do período',
            'min_value' => 'valor mínimo',
            'max_value' => 'valor máximo',
            'min_guests' => 'mínimo de convidados',
            'max_guests' => 'máximo de convidados',
            'date_field' => 'data considerada',
            'financial_situation' => 'situação financeira',
            'direction' => 'direção da ordenação',
            'search' => 'busca',
            'per_page' => 'itens por página',
        ];
    }

    public function filters(): array
    {
        $filters = array_filter(
            $this->safe()->except(['page', 'per_page']),
            fn ($value) => $value !== null && $value !== []
        );

        foreach (self::ID_LISTS as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = array_map('intval', $filters[$key]);
            }
        }

        return $filters;
    }
}
