<?php

namespace App\Http\Requests;

use App\Models\Bidding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BiddingRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agency'        => 'required|string|max:255',
            'notice_number' => 'nullable|string|max:100',
            'opening_date'  => 'nullable|date',
            'opening_time'  => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'status'        => ['required', Rule::in(Bidding::STATUSES)],
            'observation'   => 'nullable|string',
            'summary'       => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'agency.required'    => 'O órgão é obrigatório.',
            'agency.max'         => 'O órgão deve ter no máximo 255 caracteres.',
            'notice_number.max'  => 'O número do edital deve ter no máximo 100 caracteres.',
            'opening_date.date'  => 'A data de abertura não é válida.',
            'opening_time.regex' => 'O horário de abertura não é válido.',
            'status.required'    => 'A situação é obrigatória.',
            'status.in'          => 'A situação informada não é válida.',
        ];
    }
}
