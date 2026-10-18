<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ReduceClubInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $investment = $this->route('investment');

        return $this->user() !== null
            && $investment
            && (int) $investment->user_id === (int) $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
