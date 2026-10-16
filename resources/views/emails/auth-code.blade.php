<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $headline }} · {{ $brandName }}</title>
</head>
<body style="margin:0;background:#f3f6fa;color:#172033;font-family:Arial,Helvetica,sans-serif">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 12px;background:#f3f6fa">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#fff;border:1px solid #e4e9f0;border-radius:18px;overflow:hidden">
                <tr><td align="center" style="padding:30px 28px 12px">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $brandName }}" style="display:block;max-width:150px;max-height:64px;object-fit:contain;margin:0 auto 14px">
                    @else
                        <div style="display:inline-block;margin-bottom:14px;padding:10px 18px;border-radius:12px;background:{{ $brandColor }};color:#fff;font-size:18px;font-weight:bold">{{ $brandName }}</div>
                    @endif
                    <h1 style="margin:0;color:#172033;font-size:23px;line-height:1.3">{{ $headline }}</h1>
                    <p style="margin:12px auto 0;max-width:440px;color:#64748b;font-size:15px;line-height:1.6">Hello {{ $recipientName }}, {{ $intro }}</p>
                </td></tr>
                <tr><td align="center" style="padding:22px 28px">
                    <div style="display:inline-block;padding:15px 24px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;color:{{ $brandColor }};font-size:32px;font-weight:bold;letter-spacing:9px">{{ $otp }}</div>
                    <p style="margin:12px 0 0;color:#64748b;font-size:13px">This code expires in {{ $expiresMinutes }} minutes.</p>
                </td></tr>
                <tr><td style="padding:0 28px 26px">
                    <p style="margin:0;border-top:1px solid #edf0f4;padding-top:18px;color:#64748b;font-size:13px;line-height:1.6">{{ $notice }}</p>
                    <p style="margin:18px 0 0;color:#64748b;font-size:13px">Regards,<br><strong style="color:#172033">{{ $brandName }} Support</strong></p>
                </td></tr>
            </table>
            <p style="margin:16px 0 0;color:#94a3b8;font-size:11px">This is an automated security email from {{ $brandName }}. Please do not share your code.</p>
        </td></tr>
    </table>
</body>
</html>
