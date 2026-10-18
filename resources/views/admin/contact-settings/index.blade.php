@extends('layouts.admin')

@section('title', 'Contact Settings — Admin')
@section('page_title', 'Contact Page Settings')

@section('content')
<div class="dash-page">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <div class="panel">
        <div class="panel-header"><h3>Contact Information</h3></div>
        <p class="panel-sub">Manage the email, phone, and office details shown on the public Contact page.</p>

        <form method="POST" action="{{ route('admin.contact-settings.update') }}" class="dash-form admin-settings-form">
            @csrf
            @method('PUT')

            <div class="admin-settings-section">
                <h4>Contact Details</h4>
                <div class="admin-settings-grid">
                    <div class="dash-field">
                        <label for="email">Support Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $settings->email) }}" required placeholder="support@one-contract.com">
                        @error('email')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dash-field">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $settings->phone) }}" required placeholder="+1 (234) 567-890">
                        @error('phone')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dash-field">
                        <label for="support_hours">Support Hours</label>
                        <input type="text" id="support_hours" name="support_hours" value="{{ old('support_hours', $settings->support_hours) }}" required placeholder="24/7 — Always Available">
                        @error('support_hours')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dash-field">
                        <label for="office_address">Registered Office</label>
                        <input type="text" id="office_address" name="office_address" value="{{ old('office_address', $settings->office_address) }}" required placeholder="Republic of Panama">
                        @error('office_address')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="admin-settings-section">
                <h4>Social Links</h4>
                <p class="panel-sub" style="margin-top: 0; margin-bottom: 1rem;">These links appear on the Contact page and site footer. Each opens in a new tab.</p>
                <div class="admin-settings-grid">
                    <div class="dash-field">
                        <label for="social_telegram">Telegram URL</label>
                        <input type="url" id="social_telegram" name="social_telegram" value="{{ old('social_telegram', $settings->social_telegram ?: 'https://t.me/') }}" placeholder="https://t.me/yourchannel" required>
                        @error('social_telegram')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dash-field">
                        <label for="social_twitter">Twitter / X URL</label>
                        <input type="url" id="social_twitter" name="social_twitter" value="{{ old('social_twitter', $settings->social_twitter ?: 'https://x.com/') }}" placeholder="https://x.com/yourhandle">
                        @error('social_twitter')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dash-field">
                        <label for="social_facebook">Facebook URL</label>
                        <input type="url" id="social_facebook" name="social_facebook" value="{{ old('social_facebook', $settings->social_facebook ?: 'https://facebook.com/') }}" placeholder="https://facebook.com/yourpage">
                        @error('social_facebook')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dash-field">
                        <label for="social_youtube">YouTube URL</label>
                        <input type="url" id="social_youtube" name="social_youtube" value="{{ old('social_youtube', $settings->social_youtube ?: 'https://youtube.com/') }}" placeholder="https://youtube.com/@yourchannel">
                        @error('social_youtube')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Contact Settings</button>
        </form>
    </div>
</div>
@endsection
