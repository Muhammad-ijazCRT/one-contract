<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Reset Your Password — One-Contract™</title>
</head>
<body style="margin:0;padding:0;background-color:#0a0e14;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#0a0e14;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;">
                    <tr>
                        <td align="center" style="padding-bottom:24px;">
                            @php($logoPath = public_path('images/logo.png'))
                            @if (is_readable($logoPath))
                                <img src="{{ $message->embed($logoPath) }}" alt="One-Contract" width="88" height="88" style="display:block;border:0;outline:none;text-decoration:none;border-radius:12px;">
                            @else
                                <span style="font-size:22px;font-weight:700;color:#ffc107;letter-spacing:0.04em;">One-Contract™</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background:linear-gradient(180deg,#121a24 0%,#0f1724 100%);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:36px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="padding-bottom:8px;">
                                        <span style="display:inline-block;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#ffc107;">Password Reset</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:18px;">
                                        <h1 style="margin:0;font-size:26px;line-height:1.3;font-weight:700;color:#ffffff;">Reset Your Password</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:28px;">
                                        <p style="margin:0;font-size:15px;line-height:1.7;color:#94a3b8;text-align:center;">
                                            Hello <strong style="color:#ffffff;">{{ $name }}</strong>,<br>
                                            We received a request to reset the password for your One-Contract™ account. Click the button below to choose a new password.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:28px;">
                                        <a href="{{ $url }}" style="display:inline-block;background:linear-gradient(135deg,#ffc107 0%,#e6ac00 100%);color:#0a0e14;font-size:15px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:10px;box-shadow:0 8px 24px rgba(255,193,7,0.28);">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;text-align:center;">
                                            This password reset link will expire soon. If you did not request a password reset, you can safely ignore this email.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 8px 8px;text-align:center;">
                            <p style="margin:0 0 10px;font-size:12px;line-height:1.6;color:#64748b;">
                                Button not working? Copy and paste this link into your browser:
                            </p>
                            <p style="margin:0;font-size:11px;line-height:1.5;word-break:break-all;">
                                <a href="{{ $url }}" style="color:#ffc107;text-decoration:underline;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-top:20px;">
                            <p style="margin:0;font-size:12px;color:#475569;">
                                © {{ date('Y') }} One-Contract™. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
