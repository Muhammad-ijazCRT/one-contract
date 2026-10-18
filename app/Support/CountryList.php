<?php

namespace App\Support;

class CountryList
{
    /**
     * @return array<string, string>
     */
    public static function names(): array
    {
        if (! class_exists(\ResourceBundle::class)) {
            return self::fallbackNames();
        }

        $bundle = \ResourceBundle::create('en', 'ICUDATA-region');

        if (! $bundle) {
            return self::fallbackNames();
        }

        $countries = [];

        foreach ($bundle as $code => $name) {
            if (! is_string($code) || ! is_string($name) || ! preg_match('/^[A-Z]{2}$/', $code)) {
                continue;
            }

            if ($name === '' || $name === $code) {
                continue;
            }

            $countries[$code] = $name;
        }

        asort($countries);

        return $countries ?: self::fallbackNames();
    }

    /**
     * @return array<string, string>
     */
    private static function fallbackNames(): array
    {
        return [
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AR' => 'Argentina',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'BD' => 'Bangladesh',
            'BE' => 'Belgium',
            'BR' => 'Brazil',
            'CA' => 'Canada',
            'CN' => 'China',
            'EG' => 'Egypt',
            'FR' => 'France',
            'DE' => 'Germany',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IT' => 'Italy',
            'JP' => 'Japan',
            'MY' => 'Malaysia',
            'MX' => 'Mexico',
            'NL' => 'Netherlands',
            'NZ' => 'New Zealand',
            'NG' => 'Nigeria',
            'PK' => 'Pakistan',
            'PH' => 'Philippines',
            'RU' => 'Russia',
            'SA' => 'Saudi Arabia',
            'SG' => 'Singapore',
            'ZA' => 'South Africa',
            'KR' => 'South Korea',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'TH' => 'Thailand',
            'TR' => 'Turkey',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'VN' => 'Vietnam',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dialCodes(): array
    {
        static $codes = null;

        if ($codes === null) {
            $path = __DIR__.'/country-dial-codes.php';
            $codes = is_file($path) ? require $path : [];
        }

        return $codes;
    }

    public static function dialCode(?string $iso): string
    {
        if (! $iso) {
            return '';
        }

        return self::dialCodes()[strtoupper($iso)] ?? '';
    }

    public static function flagEmoji(?string $iso): string
    {
        $iso = strtoupper((string) $iso);

        if (! preg_match('/^[A-Z]{2}$/', $iso)) {
            return '';
        }

        return mb_chr(ord($iso[0]) - 65 + 0x1F1E6).mb_chr(ord($iso[1]) - 65 + 0x1F1E6);
    }

    public static function phoneLabel(?string $iso): string
    {
        $dial = self::dialCode($iso);
        $flag = self::flagEmoji($iso);

        if ($dial === '') {
            return $flag !== '' ? "{$flag} ({$iso})" : 'Select code';
        }

        return trim("{$flag} +{$dial}");
    }
}
