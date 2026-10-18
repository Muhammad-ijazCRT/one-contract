<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Verification Code — One-Contract™</title>
</head>
<body style="margin:0;padding:0;background-color:#0a0e14;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#0a0e14;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;">
                    <tr>
                        <td align="center" style="padding-bottom:24px;">
                            @php($logoSrc = \App\Support\EmailImage::url('images/logo.png'))
                            @if ($logoSrc)
                                <img src="{{ $logoSrc }}" alt="One-Contract" width="88" height="88" style="display:block;border:0;border-radius:12px;">
                            @else
                                <span style="font-size:22px;font-weight:700;color:#ffc107;">One-Contract™</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background:linear-gradient(180deg,#121a24 0%,#0f1724 100%);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:36px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="padding-bottom:8px;">
                                        <span style="display:inline-block;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#ffc107;">Withdrawal Security</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:18px;">
                                        <h1 style="margin:0;font-size:24px;line-height:1.3;font-weight:700;color:#ffffff;">Your Verification Code</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:24px;">
                                        <p style="margin:0;font-size:15px;line-height:1.7;color:#94a3b8;">
                                            Hello <strong style="color:#ffffff;">{{ $name }}</strong>,<br>
                                            Use this code to confirm your withdrawal request. It expires in 10 minutes.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:24px;">
                                        <div style="display:inline-block;background:#0a0e14;border:1px solid rgba(255,193,7,0.35);border-radius:12px;padding:16px 28px;">
                                            <span style="font-size:32px;font-weight:700;letter-spacing:0.35em;color:#ffc107;">{{ $code }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                                            If you did not request a withdrawal, ignore this email and secure your account.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-top:20px;">
                            <p style="margin:0;font-size:12px;color:#475569;">© {{ date('Y') }} One-Contract™. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
