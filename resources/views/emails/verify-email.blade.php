<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Verify Your Email Address — ONE CONTRACT™</title>
</head>
@php
    $green = '#a3ff2e';
    $black = '#000000';
    $white = '#ffffff';
    $muted = '#5f6670';
    $public = \App\Support\MailPublicUrl::base() ?: 'https://one-contract.net';
    $contact = null;

    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('contact_settings')) {
            $contact = \App\Models\ContactSetting::query()->first();
        }
    } catch (\Throwable) {
        $contact = null;
    }

    $year = date('Y');
    $logoSrc = \App\Support\EmailImage::logoUrl();
    $logoW = 44;
    $logoH = 44;
    $contactUrl = \App\Support\MailPublicUrl::page('contact') ?: $public.'/contact';
    $privacyUrl = \App\Support\MailPublicUrl::page('legal/privacy-policy') ?: $public.'/legal/privacy-policy';
    $termsUrl = \App\Support\MailPublicUrl::page('legal/terms-of-service') ?: $public.'/legal/terms-of-service';

    $socialWebsite = $contact?->website_url ?: $public;
    if (\App\Support\MailPublicUrl::isLocalHost($socialWebsite)) {
        $socialWebsite = $public;
    }
    $socialTwitter = $contact?->social_twitter ?: 'https://x.com/';
    $socialTelegram = $contact?->social_telegram ?: 'https://t.me/';
    $socialFacebook = $contact?->social_facebook ?: 'https://facebook.com/';
@endphp
<body style="margin:0;padding:0;background-color:#eceff3;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#eceff3;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background-color:{{ $white }};overflow:hidden;border-radius:0;box-shadow:0 10px 30px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:{{ $black }};padding:16px 24px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle" style="padding-right:12px;">
                                        @if ($logoSrc)
                                            <img src="{{ $logoSrc }}" alt="ONE CONTRACT" width="{{ $logoW }}" height="{{ $logoH }}" style="display:block;border:0;outline:none;text-decoration:none;width:{{ $logoW }}px;height:{{ $logoH }}px;">
                                        @else
                                            <div style="width:44px;height:44px;border-radius:10px;background:{{ $green }};color:{{ $black }};font-size:15px;font-weight:900;line-height:44px;text-align:center;">OC</div>
                                        @endif
                                    </td>
                                    <td valign="middle">
                                        <p style="margin:0;font-size:20px;line-height:1.1;font-weight:800;color:{{ $white }};letter-spacing:0.02em;">
                                            <span style="color:{{ $white }};">ONE</span>
                                            <span style="color:{{ $green }};">CONTRACT</span><span style="font-size:10px;vertical-align:super;">™</span>
                                        </p>
                                        <p style="margin:4px 0 0;font-size:9px;line-height:1.3;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:rgba(255,255,255,0.72);">
                                            One Platform. Endless Possibilities.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="line-height:0;font-size:0;background-color:{{ $black }};">
                            <div style="height:3px;background:{{ $green }};"></div>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color:{{ $white }};padding:18px 24px 6px;">
                            <p style="margin:0 0 6px;font-size:16px;line-height:1.3;font-weight:700;color:{{ $black }};">
                                Hello, {{ $name }}
                            </p>

                            <h1 style="margin:0 0 8px;font-size:26px;line-height:1.2;font-weight:800;color:{{ $black }};text-align:center;">
                                Verify Your <span style="color:{{ $green }};">Email</span> Address
                            </h1>

                            <p style="margin:0 auto 12px;max-width:500px;font-size:14px;line-height:1.5;color:{{ $muted }};text-align:center;">
                                Thank you for signing up with One Contract. To complete your registration and keep your account secure, please verify your email address.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="padding:0 0 8px;">
                                        <div style="width:48px;height:48px;margin:0 auto;border-radius:999px;background:rgba(163,255,46,0.14);border:2px solid rgba(163,255,46,0.45);line-height:48px;text-align:center;font-size:22px;">✉️</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:10px;">
                                        <p style="margin:0;font-size:14px;line-height:1.5;color:{{ $muted }};">
                                            Click the button below to verify your email address.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:12px;">
                                        <a href="{{ $url }}" style="display:inline-block;background:{{ $green }};color:{{ $black }};font-size:16px;font-weight:800;text-decoration:none;padding:14px 28px;border-radius:12px;box-shadow:0 10px 24px rgba(163,255,46,0.35);">
                                            Verify Email Address &nbsp;→
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:4px;">
                                        <p style="margin:0;font-size:13px;line-height:1.5;color:{{ $muted }};">
                                            🛡️ This link will expire in <strong style="color:{{ $green }};">24 hours</strong> for security reasons.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Features --}}
                    <tr>
                        <td style="background-color:{{ $white }};padding:18px 22px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="25%" valign="top" style="padding:10px 8px;text-align:center;">
                                        <div style="width:44px;height:44px;margin:0 auto 10px;border-radius:12px;background:rgba(163,255,46,0.14);line-height:44px;font-size:22px;">🛡️</div>
                                        <p style="margin:0 0 6px;font-size:13px;line-height:1.35;font-weight:800;color:{{ $black }};">Secure &amp; Reliable</p>
                                        <p style="margin:0;font-size:11px;line-height:1.55;color:{{ $muted }};">Bank-level security to keep your assets safe.</p>
                                    </td>
                                    <td width="25%" valign="top" style="padding:10px 8px;text-align:center;">
                                        <div style="width:44px;height:44px;margin:0 auto 10px;border-radius:12px;background:rgba(163,255,46,0.14);line-height:44px;font-size:22px;">🚀</div>
                                        <p style="margin:0 0 6px;font-size:13px;line-height:1.35;font-weight:800;color:{{ $black }};">Endless Opportunities</p>
                                        <p style="margin:0;font-size:11px;line-height:1.55;color:{{ $muted }};">Access savings, investments, trading, and more.</p>
                                    </td>
                                    <td width="25%" valign="top" style="padding:10px 8px;text-align:center;">
                                        <div style="width:44px;height:44px;margin:0 auto 10px;border-radius:12px;background:rgba(163,255,46,0.14);line-height:44px;font-size:22px;">⚡</div>
                                        <p style="margin:0 0 6px;font-size:13px;line-height:1.35;font-weight:800;color:{{ $black }};">Fast &amp; Seamless</p>
                                        <p style="margin:0;font-size:11px;line-height:1.55;color:{{ $muted }};">Enjoy smooth, instant transactions.</p>
                                    </td>
                                    <td width="25%" valign="top" style="padding:10px 8px;text-align:center;">
                                        <div style="width:44px;height:44px;margin:0 auto 10px;border-radius:12px;background:rgba(163,255,46,0.14);line-height:44px;font-size:22px;">🎧</div>
                                        <p style="margin:0 0 6px;font-size:13px;line-height:1.35;font-weight:800;color:{{ $black }};">24/7 Support</p>
                                        <p style="margin:0;font-size:11px;line-height:1.55;color:{{ $muted }};">We’re here to support you anytime.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Help box --}}
                    <tr>
                        <td style="background-color:{{ $white }};padding:16px 28px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #e4e8ee;border-radius:14px;background:#fafbfc;">
                                <tr>
                                    <td style="padding:18px 18px 18px 20px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td valign="middle" style="width:68%;padding-right:12px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td valign="middle" style="padding-right:10px;font-size:24px;line-height:1;">🎧</td>
                                                            <td valign="middle">
                                                                <p style="margin:0 0 4px;font-size:15px;line-height:1.3;font-weight:800;color:{{ $black }};">Need Help?</p>
                                                                <p style="margin:0;font-size:12px;line-height:1.55;color:{{ $muted }};">Our support team is always ready to assist you.</p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="middle" align="right" style="width:32%;">
                                                    <a href="{{ $contactUrl }}" style="display:inline-block;border:1.5px solid {{ $green }};color:{{ $green }};font-size:12px;font-weight:800;text-decoration:none;padding:10px 14px;border-radius:999px;white-space:nowrap;">
                                                        Contact Support →
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:{{ $black }};padding:26px 28px 22px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle" style="width:55%;padding-right:12px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td valign="middle" style="padding-right:10px;">
                                                    @if ($logoSrc)
                                                        <img src="{{ $logoSrc }}" alt="ONE CONTRACT" width="{{ $logoW }}" height="{{ $logoH }}" style="display:block;border:0;width:{{ $logoW }}px;height:{{ $logoH }}px;">
                                                    @else
                                                        <div style="width:42px;height:42px;border-radius:10px;background:{{ $green }};color:{{ $black }};font-size:14px;font-weight:900;line-height:42px;text-align:center;">OC</div>
                                                    @endif
                                                </td>
                                                <td valign="middle">
                                                    <p style="margin:0;font-size:16px;line-height:1.1;font-weight:800;color:{{ $white }};">
                                                        <span>ONE</span> <span style="color:{{ $green }};">CONTRACT</span><span style="font-size:9px;vertical-align:super;">™</span>
                                                    </p>
                                                    <p style="margin:5px 0 0;font-size:8px;line-height:1.4;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:rgba(255,255,255,0.62);">
                                                        One Platform. Endless Possibilities.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle" align="right" style="width:45%;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="right">
                                            <tr>
                                                <td style="padding-left:8px;">
                                                    <a href="{{ $socialWebsite }}" style="display:inline-block;width:34px;height:34px;border:1px solid rgba(163,255,46,0.35);border-radius:999px;text-align:center;line-height:34px;color:{{ $green }};text-decoration:none;font-size:14px;">⌂</a>
                                                </td>
                                                <td style="padding-left:8px;">
                                                    <a href="{{ $socialTwitter }}" style="display:inline-block;width:34px;height:34px;border:1px solid rgba(163,255,46,0.35);border-radius:999px;text-align:center;line-height:34px;color:{{ $green }};text-decoration:none;font-size:13px;font-weight:700;">X</a>
                                                </td>
                                                <td style="padding-left:8px;">
                                                    <a href="{{ $socialTelegram }}" style="display:inline-block;width:34px;height:34px;border:1px solid rgba(163,255,46,0.35);border-radius:999px;text-align:center;line-height:34px;color:{{ $green }};text-decoration:none;font-size:12px;font-weight:700;">TG</a>
                                                </td>
                                                <td style="padding-left:8px;">
                                                    <a href="{{ $socialFacebook }}" style="display:inline-block;width:34px;height:34px;border:1px solid rgba(163,255,46,0.35);border-radius:999px;text-align:center;line-height:34px;color:{{ $green }};text-decoration:none;font-size:12px;font-weight:700;">f</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding-top:20px;border-top:1px solid rgba(255,255,255,0.08);">
                                        <p style="margin:0 0 8px;font-size:11px;line-height:1.6;color:rgba(255,255,255,0.55);text-align:center;">
                                            © {{ $year }} One Contract. All rights reserved.
                                        </p>
                                        <p style="margin:0;font-size:11px;line-height:1.6;text-align:center;">
                                            <a href="{{ $privacyUrl }}" style="color:{{ $green }};text-decoration:none;">Privacy Policy</a>
                                            <span style="color:rgba(255,255,255,0.35);"> | </span>
                                            <a href="{{ $termsUrl }}" style="color:{{ $green }};text-decoration:none;">Terms of Service</a>
                                            <span style="color:rgba(255,255,255,0.35);"> | </span>
                                            <a href="{{ $contactUrl }}" style="color:{{ $green }};text-decoration:none;">Support</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Fallback link --}}
                    <tr>
                        <td style="background-color:{{ $white }};padding:0 28px 24px;">
                            <p style="margin:0;font-size:11px;line-height:1.6;color:#8b939e;text-align:center;">
                                Button not working? Copy and paste this link into your browser:<br>
                                <a href="{{ $url }}" style="color:{{ $green }};word-break:break-all;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
