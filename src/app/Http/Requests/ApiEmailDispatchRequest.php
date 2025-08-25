<?php

namespace App\Http\Requests;

use App\Enums\Common\Status;
use App\Enums\StatusEnum;
use App\Enums\System\ChannelTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiEmailDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        
        return [
            'contact' => 'required|array|min:1',
            'contact.*.email' => 'required|email|max:255',
            'contact.*.subject' => 'required|string|max:255',
            'contact.*.message' => 'required|string',
            'contact.*.gateway_identifier' => [
                'nullable',
                Rule::exists('gateways', 'uid')->where(function ($query) {
                    $query->where('status', Status::ACTIVE)
                          ->where('channel', ChannelTypeEnum::EMAIL);
                }),
            ],
            'contact.*.sender_name' => 'nullable|string|max:255',
            'contact.*.reply_to_email' => 'nullable|email|max:255',
            'contact.*.schedule_at' => 'nullable|date_format:Y-m-d H:i:s',
        ];
    }
}