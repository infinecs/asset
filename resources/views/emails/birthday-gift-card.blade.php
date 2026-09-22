<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Happy Birthday</title>
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
<td bgcolor="#4f46e5" style="background-color:#4f46e5; padding:36px 32px; text-align:center;">
<p style="margin:0 0 6px; font-size:12px; font-weight:600; letter-spacing:0.12em; text-transform:uppercase; color:#e0e7ff;">Happy Birthday</p>
<h1 style="margin:0; font-size:26px; font-weight:700; color:#ffffff;">{{ $employee->name }}</h1>
</td>
</tr>

<tr>
<td style="padding:32px;">
<p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#334155;">
    Dear {{ $employee->name }},
</p>
<p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#334155;">
    On behalf of the entire Infinecs team, we would like to wish you a very happy birthday. As a small token
    of appreciation for your contribution to the team, we are pleased to present you with a Touch 'n Go
    eWallet reload credited on your special day.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" bgcolor="#eef2ff" style="width:100%; background-color:#eef2ff; border:1px solid #c7d2fe; border-radius:10px; margin:24px 0;">
<tr>
<td style="padding:24px; text-align:center;">
<p style="margin:0 0 8px; font-size:11px; font-weight:600; letter-spacing:0.1em; text-transform:uppercase; color:#4f46e5;">Touch 'n Go eWallet Reload</p>
<p style="margin:0; font-size:22px; font-weight:700; letter-spacing:0.03em; color:#1e1b4b; font-family:'Courier New', monospace;">{{ $giftCard->gift_card_code }}</p>
<p style="margin:10px 0 0; font-size:12px; color:#64748b;">Please use this reference to redeem your reload via the Touch 'n Go eWallet app.</p>
</td>
</tr>
</table>

<p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#334155;">
    Thank you for everything you do for the team. We hope you have a wonderful celebration and a fantastic year ahead.
</p>

<p style="margin:20px 0 0; font-size:14px; line-height:1.6; color:#334155;">
    Warm regards,<br>
    <strong>The Infinecs Team</strong>
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
