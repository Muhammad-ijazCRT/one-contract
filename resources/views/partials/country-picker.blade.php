<div class="form-group {{ empty($label) ? 'form-group--no-label' : '' }}">
    @if (! empty($label))
        <label for="country_id">{{ $label }}</label>
    @endif
    <div class="country-picker" data-country-picker>
        <input
            type="hidden"
            id="country_id"
            name="country_id"
            value="{{ old('country_id', $value ?? '') }}"
            @if($required ?? true) required @endif
            data-country-value
        >
        <button type="button" class="country-picker-toggle" data-country-toggle aria-expanded="false">
            <span data-country-label>
                @php
                    $selectedCountry = old('country_id', $value ?? '');
                @endphp
                @if ($selectedCountry && isset($countries[$selectedCountry]))
                    {{ $countries[$selectedCountry] }} ({{ $selectedCountry }})
                @else
                    {{ $placeholder ?? 'Select your country ID' }}
                @endif
            </span>
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div class="country-picker-menu hidden" data-country-menu>
            <input type="text" class="country-picker-search" placeholder="Search country name or code" data-country-search>
            <div class="country-picker-list" data-country-list>
                @foreach ($countries as $code => $name)
                    <button
                        type="button"
                        class="country-picker-item"
                        data-country-option
                        data-country-name="{{ strtolower($name) }}"
                        data-country-code="{{ strtolower($code) }}"
                        data-country-value-item="{{ $code }}"
                    >
                        {{ $name }} ({{ $code }})
                    </button>
                @endforeach
            </div>
        </div>
    </div>
    @error('country_id')
        <p class="auth-field-error">{{ $message }}</p>
    @enderror
</div>
