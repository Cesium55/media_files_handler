<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoLoad extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // "video_id" => ["required", "int", "min:1"],
            'video' => ['file', 'required', 'mimes:mp4'],
            'thumb' => ['file', 'required', 'mimes:png,jpg,jpeg'],
        ];
    }
}
