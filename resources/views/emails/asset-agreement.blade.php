<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Equipment Agreement</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI', Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:32px 16px;">
<tr>
<td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,0.08);">

<tr>
<td style="background-color:#ffffff; padding:24px 32px; text-align:center; border-bottom:3px solid #4f46e5;">
<img src="{{ $message->embed(public_path('images/Infinecs-with-slogan-small.png')) }}" alt="Infinecs" height="36" style="display:inline-block;">
</td>
</tr>

<tr>
<td style="padding:32px;">
<p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#334155;">
    Dear {{ $asset->assignedEmployee?->name }},
</p>
<p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#334155;">
    The following equipment has been assigned to you. Please review and digitally sign the equipment
    agreement to confirm receipt and acceptance of responsibility.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" bgcolor="#f8fafc" style="width:100%; background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; margin:24px 0;">
<tr>
<td style="padding:20px 24px;">
<p style="margin:0 0 4px; font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#64748b;">Asset Tag</p>
<p style="margin:0 0 14px; font-size:15px; font-weight:700; color:#1e293b;">{{ $asset->asset_tag }}</p>
<p style="margin:0 0 4px; font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#64748b;">Item</p>
<p style="margin:0; font-size:15px; font-weight:700; color:#1e293b;">{{ $asset->name }}{{ $asset->model ? ' — ' . $asset->model : '' }}</p>
</td>
</tr>
</table>

<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; margin:24px 0;">
<tr>
<td align="center">
<a href="{{ route('agreements.show', $asset->agreement_token) }}" style="display:inline-block; background-color:#4f46e5; color:#ffffff; text-decoration:none; font-size:14px; font-weight:600; padding:12px 28px; border-radius:8px;">
    Review &amp; Sign Agreement
</a>
</td>
</tr>
</table>

<p style="margin:16px 0 0; font-size:12px; line-height:1.6; color:#94a3b8; word-break:break-all;">
    If the button doesn't work, copy and paste this link into your browser:<br>
    {{ route('agreements.show', $asset->agreement_token) }}
</p>
</td>
</tr>

<tr>
<td style="padding:20px 32px; background-color:#f8fafc; border-top:1px solid #e2e8f0;">
<p style="margin:0; font-size:12px; line-height:1.6; color:#94a3b8;">
    This is an automated notification from the Infinecs Asset &amp; Employee Management System. Please do not reply directly to this email.
</p>
</td>
</tr>

</table>
</td>
</tr>
</table>
</body>
</html>
