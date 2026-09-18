<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $serial }} — {{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4efe4;font-family:Georgia,serif;color:#1a1a1a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4efe4;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:12px 12px 0 0;">
                    <tr>
                        <td style="padding:22px 28px;color:#c5a059;font-size:13px;letter-spacing:.12em;text-transform:uppercase;">
                            {{ $company }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 20px;color:#fff;font-size:22px;">
                            📩 {{ $subject }}
                        </td>
                    </tr>
                </table>
                <table width="640" cellpadding="0" cellspacing="0" style="background:#fffdf8;border:1px solid #ead9b4;border-top:0;border-radius:0 0 12px 12px;">
                    <tr>
                        <td style="padding:22px 28px;font-size:15px;line-height:1.55;">
                            <p style="margin:0 0 14px;color:#7a6238;font-size:13px;">
                                Serial No: <strong>{{ $serial }}</strong>
                                &nbsp;·&nbsp; {{ $sent_at }}
                            </p>
                            <p>Bonjour <strong>{{ $name }}</strong>, / Hello <strong>{{ $name }}</strong>,</p>
                            <p>Vous avez reçu un message depuis le site.<br>You have received a message from the website.</p>
                            <table width="100%" cellpadding="6" cellspacing="0" style="margin:16px 0;font-size:14px;">
                                <tr><td width="120" style="color:#7a6238;">Name</td><td><strong>{{ $name }}</strong></td></tr>
                                @if($phone)
                                <tr><td style="color:#7a6238;">Phone</td><td>{{ $phone }}</td></tr>
                                @endif
                                <tr><td style="color:#7a6238;">Email</td><td><a href="mailto:{{ $email }}">{{ $email }}</a></td></tr>
                                <tr><td style="color:#7a6238;">Subject</td><td>{{ $subject }}</td></tr>
                            </table>
                            <p style="color:#7a6238;margin-bottom:6px;">Message</p>
                            <div style="background:#fff8ea;border-left:4px solid #c5a059;padding:12px 14px;white-space:pre-wrap;">{{ $body }}</div>
                            <p style="margin-top:22px;">Cordialement, / Kind regards,<br><strong>{{ $company }}</strong></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
