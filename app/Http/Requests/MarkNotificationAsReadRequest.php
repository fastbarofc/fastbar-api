<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkNotificationAsReadRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        $notification = $this->route('notification');

        return $notification && $this->user() && $notification->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [];
    }
}

