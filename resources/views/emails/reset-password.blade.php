@extends('emails.layout')

@section('title', 'Reset your password')

@section('preheader', 'Reset your Kay Factory Music password — this link expires in ' . $expireMinutes . ' minutes.')

@section('content')

<div style="font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#E4B84C;margin-bottom:16px;">
Password Reset
</div>

<h1 style="font-size:26px;font-weight:700;color:#FAFAFA;margin:0 0 20px;line-height:1.2;letter-spacing:-0.3px;">
Reset your password
</h1>

<p style="font-size:15px;line-height:1.7;color:#E8E6E1;margin:0 0 16px;">
Hi {{ $name }},
</p>

<p style="font-size:15px;line-height:1.7;color:#E8E6E1;margin:0 0 32px;">
We received a request to reset your Kay Factory Music account password. Click the button below to choose a new one.
</p>

{{-- CTA Button --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
<tr>
<td align="left" style="background-color:#E4B84C;">
  <a href="{{ $url }}" style="display:inline-block;padding:16px 32px;font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#0A0A0A;text-decoration:none;">
    Reset Password
  </a>
</td>
</tr>
</table>

<p style="font-size:14px;line-height:1.6;color:#8A8A94;margin:0 0 24px;">
This link expires in <strong style="color:#E8E6E1;">{{ $expireMinutes }} minutes</strong>. For your security, it can only be used once.
</p>

{{-- Fallback URL --}}
<div style="background-color:#0E0E10;border:1px solid #232326;padding:16px;margin-bottom:24px;">
  <div style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#5A5A66;margin-bottom:8px;">
    Or copy this link into your browser
  </div>
  <div style="font-size:12px;color:#8A8A94;word-break:break-all;line-height:1.6;">
    {{ $url }}
  </div>
</div>

<p style="font-size:13px;line-height:1.7;color:#5A5A66;margin:0;">
If you did not request a password reset, you can safely ignore this email. Your password will not change.
</p>

@endsection