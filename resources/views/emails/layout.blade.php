{{-- Email clients ignore stylesheets and most modern CSS, so this layout uses
     tables and inline styles rather than the Tailwind the rest of the app
     relies on. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('subject', 'RANEY LUBRICANTS TRADING')</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f6f9; font-family:Arial,Helvetica,sans-serif; color:#16202a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f6f9; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; border:1px solid rgba(21,35,54,0.10);">

                    <tr>
                        <td style="background-color:#0d1f18; padding:24px 28px;">
                            <div style="font-size:18px; font-weight:bold; letter-spacing:-0.3px;">
                                <span style="color:#148a67;">RANEY</span><span style="color:#d9b14a;"> LUBRICANTS</span>
                            </div>
                            <div style="margin-top:4px; font-size:10px; letter-spacing:3px; text-transform:uppercase; color:rgba(255,255,255,0.5);">Trading</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f6f8fb; padding:20px 28px; border-top:1px solid rgba(21,35,54,0.10);">
                            <p style="margin:0; font-size:12px; line-height:18px; color:#6f7d8c;">
                                RANEY LUBRICANTS TRADING<br>
                                123 Industrial Ave, Makati City, Metro Manila<br>
                                Mon - Sat: 8AM - 6PM
                            </p>
                            <p style="margin:12px 0 0; font-size:11px; color:#96a1ad;">
                                This message was sent automatically. Please do not reply to it.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
