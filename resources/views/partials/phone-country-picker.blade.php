@php
    use App\Support\CountryList;

    $selectedCountry = old('country_id', $value ?? ($default ?? 'NG'));
    $selectedDial = CountryList::dialCode($selectedCountry);
    $selectedFlag = CountryList::flagEmoji($selectedCountry);
@endphp
<div class="form-group form-group--phone-dial {{ empty($label) ? 'form-group--no-label' : '' }}">
    @if (! empty($label))
        <label for="country_id">{{ $label }}</label>
    @endif
    <div class="country-picker country-picker--phone" data-country-picker data-country-picker-variant="phone">
        <input
            type="hidden"
            id="country_id"
            name="country_id"
            value="{{ $selectedCountry }}"
            @if($required ?? true) required @endif
            data-country-value
        >
        <button type="button" class="country-picker-toggle" data-country-toggle aria-expanded="false">
            <span data-country-label>
                @if ($selectedCountry && $selectedDial)
                    <span class="oc-phone-flag" aria-hidden="true">{{ $selectedFlag }}</span>
                    <span class="oc-phone-code">+{{ $selectedDial }}</span>
                @else
                    {{ $placeholder ?? '+234' }}
                @endif
            </span>
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div class="country-picker-menu hidden" data-country-menu>
            <input type="text" class="country-picker-search" placeholder="Search country" data-country-search>
            <div class="country-picker-list" data-country-list>
                @foreach ($countries as $code => $name)
                    @php $dial = CountryList::dialCode($code); @endphp
                    <button
                        type="button"
                        class="country-picker-item"
                        data-country-option
                        data-country-name="{{ strtolower($name) }}"
                        data-country-code="{{ strtolower($code) }}"
                        data-country-value-item="{{ $code }}"
                        data-country-dial="{{ $dial }}"
                        data-country-flag="{{ CountryList::flagEmoji($code) }}"
                    >
                        <span class="oc-phone-flag" aria-hidden="true">{{ CountryList::flagEmoji($code) }}</span>
                        <span>{{ $name }}</span>
                        @if ($dial)
                            <small>+{{ $dial }}</small>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>
    @error('country_id')
        <p class="auth-field-error">{{ $message }}</p>
    @enderror
</div>
