<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('site.name') }}</title>
</head>
<body style="margin:0; padding:0; background:#F7F4EE; font-family:Arial, Helvetica, sans-serif; color:#0B2530;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F4EE; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">
                    <tr>
                        <td style="padding:0 8px 18px; font-family:Georgia, 'Times New Roman', serif; font-size:19px; font-weight:bold; color:#0B3B3C;">
                            {{ config('site.name') }}
                            <div style="font-family:Arial, Helvetica, sans-serif; font-size:11px; font-weight:normal; letter-spacing:1px; text-transform:uppercase; color:#52676C; padding-top:4px;">{{ config('site.tagline') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#FFFFFF; border:1px solid #E4DED3; border-radius:16px; padding:32px; font-size:16px; line-height:1.65; color:#3F5559;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 8px 0; font-size:12px; line-height:1.6; color:#7A8B8E;">
                            {{ config('site.name') }} · Educational guidance only — not legal or immigration advice.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
