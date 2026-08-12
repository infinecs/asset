<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gift Card Reminder</title>
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
<p style="margin:0 0 4px; font-size:13px; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; color:#b45309;">
    @if($daysUntil === 0)
    Due Today
    @else
    Action Needed &mdash; {{ $daysUntil }} day{{ $daysUntil === 1 ? '' : 's' }} left
    @endif
</p>
<h1 style="margin:0 0 16px; font-size:20px; color:#0f172a;">Prepare {{ $employee->name }}'s Touch 'n Go eWallet Reload</h1>

<p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#334155;">
    {{ $employee->name }} ({{ $employee->id_number }}) has a birthday coming up on
    <strong>{{ $giftCard->birthday_date->format('d F Y') }}</strong>. A Touch 'n Go eWallet reload has not yet
    been prepared for this occasion.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin:0 0 20px;">
<tr>
<td style="padding:14px 18px; font-size:13px; color:#64748b;">Employee</td>
<td style="padding:14px 18px; font-size:13px; color:#0f172a; font-weight:600; text-align:right;">{{ $employee->name }}</td>
</tr>
<tr>
<td style="padding:0 18px 14px; font-size:13px; color:#64748b; border-top:1px solid #e2e8f0; padding-top:14px;">Birthday</td>
<td style="padding:0 18px 14px; font-size:13px; color:#0f172a; font-weight:600; text-align:right; border-top:1px solid #e2e8f0; padding-top:14px;">{{ $giftCard->birthday_date->format('d M Y') }}</td>
</tr>
<tr>
<td style="padding:0 18px 14px; font-size:13px; color:#64748b; border-top:1px solid #e2e8f0; padding-top:14px;">Touch 'n Go eWallet Reload</td>
<td style="padding:0 18px 14px; font-size:13px; color:#dc2626; font-weight:600; text-align:right; border-top:1px solid #e2e8f0; padding-top:14px;">Not set</td>
</tr>
</table>

<table role="presentation" cellpadding="0" cellspacing="0">
<tr>
<td bgcolor="#4f46e5" style="border-radius:8px; background-color:#4f46e5;">
<a href="{{ route('gift-cards.index') }}" target="_blank" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none; background-color:#4f46e5; border-radius:8px;">Open Gift Cards Dashboard</a>
</td>
</tr>
</table>

<p style="margin:24px 0 0; font-size:12px; line-height:1.6; color:#94a3b8;">
    This is reminder #{{ $giftCard->reminder_count }}. You will continue to receive this notification daily until the reload reference is entered.
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
