<?php

namespace App\Http\Requests;

use App\Enums\Common\Status;
use App\Enums\System\ChannelTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiSmsDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'contact' => 'required|array|min:1',
            'contact.*.number' => 'required|max:255',
            'contact.*.message' => 'required|string',
            'contact.*.gateway_identifier' => [
                'nullable',
            ],
            'contact.*.schedule_at' => 'nullable|date_format:Y-m-d H:i:s',
        ];
        return $rules;
    }
}