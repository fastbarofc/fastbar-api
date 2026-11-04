<?php

namespace App\Http\Requests;

class ChartRequest extends ReportRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['year' => 'ano'];
    }
}
