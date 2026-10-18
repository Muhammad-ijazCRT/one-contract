<?php

namespace App\Http\Requests\User;

use App\Enums\WalletCurrency;
use App\Services\PlatformSettings;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepositRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:'.PlatformSettings::gatewayMinDeposit(), 'max:50000'],
            'currency' => ['required', 'string', 'in:'.WalletCurrency::cryptoCodes()],
            'network' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $min = number_format(PlatformSettings::gatewayMinDeposit(), 2);

        return [
            'amount.min' => 'Minimum deposit is $'.$min.' USD. Enter the dollar value, not the coin amount.',
            'amount.max' => 'Maximum deposit is $50,000 USD.',
        ];
    }
}