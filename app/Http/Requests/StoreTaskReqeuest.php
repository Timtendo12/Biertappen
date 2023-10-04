<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskReqeuest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'task' => 'required|string|max:255', //TODO: Determine max length.. (Is this accurate?)
            'players' => 'required|integer|min:0|max:15',
            'type' => 'required|string|',
            'duration' => 'required|integer|min:0',
            'pack_id' => 'required|integer|min:0',
        ];
    }
}
