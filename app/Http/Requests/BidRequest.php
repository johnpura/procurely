<?php

namespace App\Http\Requests;

use App\Enums\BidStatus;
use App\Models\Bid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $bid = $this->route('bid');

        return $bid
            ? $this->user()->can('update', $bid)
            : $this->user()->can('create', Bid::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bid = $this->route('bid');

        return [
            'reference_number' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('bids', 'reference_number')->ignore($bid?->id)],
            'title' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:20000'],
            'closes_at' => [Rule::requiredIf($bid && $bid->status !== BidStatus::Draft), 'nullable', 'date'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at')],
        ];
    }
}