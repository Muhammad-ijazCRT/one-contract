<?php

namespace App\Http\Requests\User;

use App\Enums\CryptoNetwork;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCapitalWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'network' => ['required', Rule::in(CryptoNetwork::values())],
            'wallet_address' => ['required', 'string', 'min:10', 'max:255'],
            'email_otp' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email_otp.required' => 'Enter the verification code sent to your email.',
            'email_otp.size' => 'The verification code must be 6 digits.',
        ];
    }
}
