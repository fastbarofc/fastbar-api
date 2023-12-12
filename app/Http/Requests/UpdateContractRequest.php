<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => 'nullable|exists:events,id,' . $this->route('event')->id,
        ];
    }

    public function messages() 
    {
        return [
            'event_id.exists' => 'O ID evento deve ser um ID válido',
        ];
    }
}

