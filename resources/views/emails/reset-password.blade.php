@extends('emails.layout')

@section('subject', 'Reset your password')

@section('content')
    <h1 style="margin:0 0 8px; font-size:22px; color:#16202a;">Reset your password</h1>
    <p style="margin:0 0 20px; font-size:14px; line-height:22px; color:#6f7d8c;">
        Hello {{ $user->full_name }}, somebody asked to reset the password for the
        RANEY LUBRICANTS TRADING account registered to this address.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td style="background-color:#148a67; border-radius:10px;">
                <a href="{{ $url }}" style="display:inline-block; padding:14px 26px; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none;">Choose a new password</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:12px; line-height:19px; color:#6f7d8c;">
        The link expires in {{ $minutes }} minutes and can only be used once. If the button does not work,
        paste this into your browser:
    </p>
    <p style="margin:0 0 20px; font-size:11px; line-height:17px; color:#148a67; word-break:break-all;">{{ $url }}</p>

    <p style="margin:0; font-size:12px; line-height:19px; color:#96a1ad;">
        If this was not you, ignore this email. Your password stays as it is, and nobody can change it
        without the link above.
    </p>
@endsection
