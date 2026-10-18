<?php

namespace App\Http\Requests\User;

use App\Enums\TermsDocument;
use App\Services\PlatformSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EnrollSavingsPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $min = PlatformSettings::savingsMinDaily();
        $max = PlatformSettings::savingsMaxDaily();

        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', "min:{$min}", "max:{$max}"],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->user()?->hasAcceptedTerms(TermsDocument::SavingsPlan)) {
                $validator->errors()->add('terms', 'Please accept the Savings Plan Terms & Conditions first.');
            }
        });
    }
}
