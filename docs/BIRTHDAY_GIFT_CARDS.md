# Birthday Gift Card Program — Setup & PIC Guide

This document explains how the Birthday Gift Card feature works, how to set it up, and — most
importantly — what happens if the Touch 'n Go eWallet reload is **not** prepared in time.

It's written for two audiences:

- **Admin** — one-time setup (assigning the Person In Charge, employee data)
- **Person In Charge (PIC)** — the day-to-day job of preparing gift cards when reminded

---

## 1. What this feature does

Every day at **8:00 AM**, the system automatically checks all active employees' birthdays. For
any employee whose birthday is **within the next 7 days**, it will:

- Email the **Person(s) In Charge (PIC)** a reminder to prepare a Touch 'n Go eWallet reload,
  **every single day**, for as long as the reload reference is left blank.
- On the **actual birthday**, if a reload reference has been entered, automatically email the
  **employee** their birthday gift card.

No one needs to log in and check manually — the whole cycle runs by itself, as long as the setup
below is in place and someone acts on the reminder emails.

---

## 2. One-time setup (Admin)

### 2.1 Make sure employees have a Date of Birth on file

The system can't remind anyone about a birthday it doesn't know. Go to **Employees**, and either:

- Fill in **Date of Birth** individually via **Edit** on each employee, or
- Use **Employees → Bulk Edit Birthdays** to fill in many at once (dates are entered as
  `dd/mm/yyyy`). This page also has a **"Missing only"** filter to quickly find who still needs
  one.

Only employees with a Date of Birth on file, an **Active** status, and who have **not opted out**
(see 2.3) are included in the daily check.

### 2.2 Assign the Person(s) In Charge

Go to **Gift Cards → Settings** and select one or more employees as **Person(s) In Charge**. Every
person selected here receives every reminder email — you can assign more than one person (e.g. as
backup cover).

> ⚠️ If no PIC is assigned, reminder emails simply won't be sent — the Gift Cards page will show a
> red warning banner at the top until this is set.

### 2.3 Opting employees out (optional)

If a specific employee should **never** receive a birthday gift card (e.g. by their own request),
open their **Edit** page and check **"Opt out of the birthday gift card program"**. They'll be
fully excluded from reminders and birthday emails going forward. You can see everyone who's opted
out via the **Gift Cards** page → **View: Opted out**.

---

## 3. The reminder timeline

Here's exactly what happens counting down to an employee's birthday, assuming the reload
reference is still blank:

| When | What happens |
|---|---|
| **7 days before** | First reminder email sent to all PICs. |
| **6, 5, 4, 3, 2, 1 days before** | Another reminder email sent **every day** the reference is still blank. Each email shows how many reminders have been sent so far (e.g. "This is reminder #4"). |
| **Day of birthday, still blank** | One final, urgent reminder ("Due Today") is sent to the PICs. |
| **Day of birthday, reference filled in** | Instead of a reminder, the employee is automatically emailed their birthday gift card. The record is marked **Sent** and moves to the **"Sent this year"** view on the Gift Cards page. |
| **Day after the birthday** | The reminder window closes for this employee, for this year (see Section 4). |

As soon as a PIC fills in the reload reference on the **Gift Cards** page (at any point during the
7-day window), the daily reminders for that employee **stop immediately** — the record moves to
**Ready**, and the birthday email will go out automatically on the day itself.

---

## 4. ⚠️ What happens if the reload reference is NOT set in time

This is the important part.

**If nobody fills in the Touch 'n Go eWallet reload reference by the end of the employee's actual
birthday, the system gives up on that occasion — quietly.**

Specifically:

- The **employee will not receive a gift card email at all** for that birthday. There is no
  automatic "late" or "catch-up" send — the birthday email is *only* triggered on the exact day,
  and only if a reference is present at that time.
- **Reminder emails to the PIC stop the very next day.** The 7-day window is calculated relative
  to the employee's *upcoming* birthday — once the birthday has passed, the system starts counting
  down to *next year's* birthday instead, which is far outside the 7-day window. Nothing more is
  sent until the reminder window opens again next year.
- The missed record isn't deleted — it stays in the database as a historical row (status stuck at
  **"Reminder Sent"**), but there is no page in the app that surfaces "missed" gift cards
  separately, and no further automated nudge will occur.
- An admin *can* still find the employee under **View: Upcoming** with the range filter set to
  **"Full year"** (they won't show under the default "Next 60 days" until closer to the date) and
  fill in a reference ahead of time, but this will only be used for **next year's** birthday, not
  a retroactive gift for the one that was missed.

**In short: a missed reload reference means that employee simply doesn't get a birthday gift card
for that year, with no safety net.** The daily reminders exist specifically to prevent this — if
you're the PIC, treat each reminder email as a real deadline, not a suggestion.

---

## 5. What the PIC needs to do, day to day

1. **Watch for the reminder email**, subject line: *"Action needed: prepare [Name]'s birthday
   gift card"* (or *"Due Today"* on the final day).
2. Click **Open Gift Cards Dashboard** in the email, or go to **Gift Cards** in the app directly.
3. Find the employee's row (sorted by soonest birthday first) and click the **pencil icon** next
   to their reload field.
4. Purchase/generate the Touch 'n Go eWallet reload and enter the reference (code, transaction ID,
   or whatever your process uses to identify it) into the field, then click the checkmark to save.
5. That's it — the daily reminders stop, and the employee is emailed automatically on their
   birthday. No further action needed unless you want to double-check delivery (see below).

### Double-checking before the big day

Once a reference is entered, use the **paper-plane "Test Send" icon** on that employee's row. This
sends the *exact* birthday email — with the real reference — to **your own email address** (never
to the employee), so you can confirm it looks right before the real send goes out automatically.

---

## 6. Other useful pages

| Page | Purpose |
|---|---|
| **Gift Cards** (`/gift-cards`) | Main dashboard — upcoming birthdays, reload status, reminders. Use the **View** dropdown to switch between **Upcoming**, **Sent this year**, and **Opted out**. |
| **Gift Cards → Settings** | Assign/change the Person(s) In Charge. |
| **Gift Cards → Preview Template** | View the birthday email design with sample data, without sending anything. |
| **Employees → Bulk Edit Birthdays** | Fill in Date of Birth for many employees at once (`dd/mm/yyyy` format), with a filter for employees still missing one. |

---

## 7. FAQ

**Q: Can I set the reload reference before the 7-day window opens?**
Yes — the field is editable at any time once the employee's row appears on the **Upcoming** view
(within the next 60 days, or switch the range filter to "Full year" to see further out). Filling
it in early means no reminders will ever be triggered for that birthday, since the code is already
present by the time the window opens.

**Q: What if I make a typo in the reference after the card was already sent?**
You can still edit it after the fact — correcting a reference on an already-**Sent** record (found
under **View: Sent this year**) does not re-trigger sending or change its status.

**Q: Who receives the reminder if there are multiple PICs?**
All of them, individually, every day the reference is blank within the window.

**Q: Does this work for employees without an email address or who are marked Resigned?**
No — only **Active** employees with a Date of Birth on file are included.

**Q: I missed one — can I send it late?**
Not automatically. See Section 4. An admin can manually send the employee their gift card email
by preparing the record and using **Test Send** — but note Test Send always delivers to *your own*
address, not the employee's, so a genuinely late manual send would need to be actioned separately
outside the app.
