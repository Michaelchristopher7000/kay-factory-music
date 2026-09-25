<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Kay Factory Music')</title>
</head>
<body style="margin:0;padding:0;background-color:#0A0A0A;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#E8E6E1;-webkit-font-smoothing:antialiased;">

{{-- Preheader (hidden preview text) --}}
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
@yield('preheader')
</div>

{{-- Outer wrapper --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0A0A0A;">
<tr>
<td align="center" style="padding:40px 16px;">

  {{-- Main container --}}
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#141416;border:1px solid #232326;">

    {{-- Brand header --}}
    <tr>
      <td align="left" style="padding:28px 40px;border-bottom:1px solid #232326;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="vertical-align:middle;padding-right:12px;">
              <div style="width:36px;height:36px;background-color:#E4B84C;text-align:center;line-height:36px;font-size:20px;font-weight:700;color:#0A0A0A;">&#9835;</div>
            </td>
            <td style="vertical-align:middle;">
              <div style="font-size:15px;font-weight:700;letter-spacing:2px;color:#FAFAFA;text-transform:uppercase;">Kay Factory Music</div>
              <div style="font-size:11px;letter-spacing:2px;color:#6B6B70;text-transform:uppercase;margin-top:2px;">Where Sound Becomes Legacy.</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- Body --}}
    <tr>
      <td style="padding:40px;">
        @yield('content')
      </td>
    </tr>

    {{-- Footer --}}
    <tr>
      <td style="padding:28px 40px;background-color:#0E0E10;border-top:1px solid #232326;">
        <div style="font-size:13px;font-weight:600;color:#FAFAFA;margin-bottom:4px;">Kay Factory Music</div>
        <div style="font-size:12px;color:#6B6B70;margin-bottom:20px;">Where Sound Becomes Legacy.</div>

        <div style="font-size:11px;margin-bottom:16px;">
          <a href="{{ url('/privacy-policy') }}" style="color:#8A8A94;text-decoration:none;margin-right:16px;">Privacy Policy</a>
          <a href="{{ url('/terms') }}" style="color:#8A8A94;text-decoration:none;margin-right:16px;">Terms &amp; Conditions</a>
          <a href="{{ url('/cookie-policy') }}" style="color:#8A8A94;text-decoration:none;">Cookie Policy</a>
        </div>

        <div style="font-size:11px;color:#5A5A66;">&copy; {{ date('Y') }} Kay Factory Music. All rights reserved.</div>
      </td>
    </tr>

  </table>

</td>
</tr>
</table>

</body>
</html>