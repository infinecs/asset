<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upcoming Birthday</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI', Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:32px 16px;">
<tr>
<td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,0.08);">

<tr>
<td style="background-color:#ffffff; padding:24px 32px; border-bottom:3px solid #4f46e5;">
<img src="{{ $message->embed(public_path('images/Infinecs-with-slogan-small.png')) }}" alt="Infinecs" height="36" style="display:block;">
</td>
</tr>

<tr>
<td style="padding:32px;">
<p style="margin:0 0 4px; font-size:13px; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; color:#4f46e5;">
    {{ $daysUntil }} day{{ $daysUntil === 1 ? '' : 's' }} to go
</p>
<h1 style="margin:0 0 16px; font-size:20px; color:#0f172a;">{{ $employee->name }}'s birthday is coming up</h1>

<p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#334155;">
    Hi {{ $manager->name }},
</p>
<p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#334155;">
    This is a heads up that <strong>{{ $employee->name }}</strong> ({{ $employee->id_number }}), who reports to you,
    has a birthday coming up on <strong>{{ $employee->date_of_birth?->format('d F') }}</strong>. HR is already
    taking care of the birthday gift card &mdash; no action is needed from you, this is just a friendly reminder
    in case you'd like to wish them well.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin:0 0 20px;">
<tr>
<td style="padding:14px 18px; font-size:13px; color:#64748b;">Employee</td>
<td style="padding:14px 18px; font-size:13px; color:#0f172a; font-weight:600; text-align:right;">{{ $employee->name }}</td>
</tr>
<tr>
<td style="padding:0 18px 14px; font-size:13px; color:#64748b; border-top:1px solid #e2e8f0; padding-top:14px;">Birthday</td>
<td style="padding:0 18px 14px; font-size:13px; color:#0f172a; font-weight:600; text-align:right; border-top:1px solid #e2e8f0; padding-top:14px;">{{ $employee->date_of_birth?->format('d M Y') }}</td>
</tr>
</table>

<p style="margin:24px 0 0; font-size:12px; line-height:1.6; color:#94a3b8;">
    You are receiving this because you are set as {{ $employee->name }}'s manager in the Infinecs system.
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
