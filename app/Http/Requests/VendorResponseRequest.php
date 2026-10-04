<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'cover_note' => ['nullable', 'string', 'max:5000'],
            'files' => ['required', 'array', 'min:1', 'max:5'],
            'files.*' => ['file', 'mimes:pdf', 'max:20480'],
            'acknowledge' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Attach at least one PDF file.',
            'files.max' => 'You can attach up to 5 files.',
            'files.*.mimes' => 'Only PDF files are accepted.',
            'files.*.max' => 'Each file must be 20 MB or smaller.',
            'acknowledge.accepted' => 'Please confirm that you understand the response cannot be changed once submitted.',
        ];
    }
}
