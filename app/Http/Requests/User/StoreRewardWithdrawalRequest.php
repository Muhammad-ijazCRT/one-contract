<?php

namespace App\Http\Requests\User;

use App\Enums\CryptoNetwork;
use App\Enums\WalletCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRewardWithdrawalRequest extends FormRequest
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
            'currency' => ['required', 'string', 'in:'.WalletCurrency::cryptoCodes()],
            'amount' => ['required', 'numeric', 'min:0.01'],
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
