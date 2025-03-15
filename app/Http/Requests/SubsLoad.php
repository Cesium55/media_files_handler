<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubsLoad extends FormRequest
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
            // "video_id" => "required|int",
            'files' => ['required', 'array'],
            "files.*" => [
                "required",
                "mimes:srt",
                function ($attribute, $value, $fail) {
                    $lang = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);
                    if (!in_array($lang, config("media_files.allowed_langs"))) {
                        $fail("Filename " . $lang . " is not allowed");
                    }
                }
            ]

        ];
    }

}
