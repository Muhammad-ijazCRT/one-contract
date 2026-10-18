<?php

namespace App\Http\Requests\User;

use App\Enums\TermsDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InvestClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $club = $this->route('club');
        $min = $club ? (float) $club->min_investment : 0.01;
        $max = $club && $club->max_investment !== null ? (float) $club->max_investment : 450;

        return [
            'amount' => ['required', 'numeric', "min:{$min}", "max:{$max}"],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->user()?->hasAcceptedTerms(TermsDocument::InvestmentClubs)) {
                $validator->errors()->add('terms', 'Please accept the Investment Schemes Terms & Conditions first.');
            }
        });
    }
}
