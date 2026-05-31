<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                // MongoDB stores integer PKs in the 'id' field, not '_id'.
                // Rule::unique()->ignore() resolves to _id which is wrong here.
                // Use a where() condition on the 'id' field instead.
                Rule::unique(User::class, 'email')
                    ->where(fn ($q) => $q->where('id', '!=', $userId)),
            ],
        ];
    }
}
