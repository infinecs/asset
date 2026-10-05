# Running the Scheduler / Cron Job on a Server

This app uses Laravel's task scheduler to run jobs automatically — right now that's a single job,
**`gift-cards:process`**, registered in `routes/console.php` to run daily at **08:00
(Asia/Kuala_Lumpur)**:

```php
Schedule::command('gift-cards:process')->dailyAt('08:00');
```

This document explains what needs to be set up on the actual server for that line to ever run.
This is a one-time, per-server setup step — it is **not** something that happens automatically
just because the code is deployed.

---

## 1. Why this is needed

Laravel's scheduler is not a background daemon by itself. `Schedule::command(...)->dailyAt(...)`
only *describes* when a job should run — something on the server still has to actually check the
schedule and fire it at the right time.

That "something" is a single command:

```bash
php artisan schedule:run
```

This command checks all registered scheduled tasks and runs any that are due **right now**. It
does nothing if nothing is due. Laravel's documented approach is to have the server's own cron (or
Windows Task Scheduler) call this command **every single minute** — the scheduler itself figures
out which minute is actually the right one to fire `gift-cards:process`.

So: **one cron entry, running every minute, forever** — not one entry per job. If you ever add more
scheduled commands to `routes/console.php` later, you do **not** need a new cron entry for each one;
they all ride on the same `schedule:run` heartbeat.

---

## 2. Linux / Unix server setup (most production hosting)

1. SSH into the server.
2. Edit the crontab for the user that owns the application (commonly `www-data`, `forge`, or your
   deploy user):

   ```bash
   crontab -e
   ```

3. Add this single line, using the **absolute path** to both PHP and the project's `artisan` file:

   ```cron
   * * * * * cd /var/www/asset && php artisan schedule:run >> /dev/null 2>&1
   ```

   Adjust `/var/www/asset` to wherever this project actually lives on that server. If `php` isn't
   the right binary for your server's PHP-CLI (e.g. you need a specific version), use its full
   path instead, e.g. `/usr/bin/php8.3`.

4. Save and exit. That's it — no restart needed, cron picks it up immediately.

### Verifying it on Linux

```bash
# Confirm the job is registered and see when it's next due
php artisan schedule:list

# Manually trigger a check right now (runs anything currently due)
php artisan schedule:run

# Watch what's due without actually running it
php artisan schedule:list
```

You can also tail `storage/logs/laravel.log` after 08:00 to confirm `gift-cards:process` actually
ran (it logs a summary line like `Done. 1 gift card(s) sent, 2 reminder(s) sent.`).

---

## 3. Windows Server setup (Task Scheduler)

If the app is hosted on Windows (IIS, Laragon-in-production, or similar), use **Task Scheduler**
instead of cron:

1. Open **Task Scheduler** → **Create Task…** (not "Create Basic Task" — the full dialog gives
   more control).
2. **General tab**: give it a name like `Laravel Scheduler - Asset App`. Under **Security options**,
   choose a user account that has permission to run PHP and access the project folder, and check
   **"Run whether user is logged on or not"**.
3. **Triggers tab** → **New…** → Begin the task **On a schedule** → **Daily**, recur every 1 day,
   and check **"Repeat task every: 1 minute"** for a duration of **"Indefinitely"**.
4. **Actions tab** → **New…**:
   - **Program/script**: the full path to `php.exe`, e.g.
     `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`
   - **Add arguments**: `artisan schedule:run`
   - **Start in**: the project root, e.g. `C:\laragon\www\asset`

   (The "Start in" field matters — it's what makes `artisan` resolve correctly without needing a
   full path to it.)
5. Save. You may be prompted for the account's password.

### Verifying it on Windows

Open PowerShell in the project folder and run:

```powershell
php artisan schedule:list
php artisan schedule:run
```

You can also right-click the task in Task Scheduler → **Run**, then check **Last Run Result**
(should be `0x0` / "The operation completed successfully") and `storage\logs\laravel.log` for the
job's summary line.

---

## 4. Running it locally during development (Laragon)

Laragon does **not** run cron or Task Scheduler jobs for you automatically — the `dailyAt('08:00')`
schedule will simply never fire unless something calls `schedule:run` on a loop.

For local testing, you don't need to set up a real recurring scheduler at all — just run the
actual job directly whenever you want to test it:

```bash
php artisan gift-cards:process
```

If you specifically want to simulate the "every minute" scheduler loop locally (e.g. to test that
`dailyAt('08:00')` actually fires at the right time), Laravel provides a convenience command that
does this for you in the foreground — leave it running in a terminal:

```bash
php artisan schedule:work
```

This is a **development-only** convenience (it's just a loop that calls `schedule:run` every
minute for you) — don't use it in production; use real cron / Task Scheduler there instead, since
`schedule:work` stops the moment you close the terminal.

---

## 5. Troubleshooting

| Symptom | Likely cause |
|---|---|
| Nothing happens at 8am, ever | The OS-level cron / Task Scheduler entry was never actually created — this is the #1 cause. Check `php artisan schedule:list` shows the job with a sensible "Next Due" time; that only confirms the code is registered, **not** that anything is actually calling `schedule:run`. |
| Works when run manually, not automatically | The cron user / Task Scheduler account doesn't have the same PHP version or environment as your interactive shell. Always use absolute paths to `php` and to the project folder in the scheduled entry, never rely on `PATH`. |
| Runs, but `.env` values seem wrong / missing | The service account running the task may not have read access to `.env`, or is running from the wrong working directory. Double-check the "Start in" / `cd` path matches the actual project root. |
| Emails aren't sending, but the log shows the job ran | That's a mail configuration issue, not a scheduler issue — check `MAIL_MAILER` / `MAIL_HOST` / `MAIL_SCHEME` in `.env` separately. |
| Job runs multiple times a minute / duplicated sends | Something is running `schedule:run` more than once per minute (e.g. both a cron entry **and** `schedule:work` left running). Only one should be active at a time. |
| Wrong time of day | Confirm server timezone matches `config/app.php`'s `timezone` setting (`Asia/Kuala_Lumpur` for this app) — `dailyAt('08:00')` is evaluated in the app's configured timezone, not the server's system clock timezone, but if they're out of sync it can still cause confusing "next due" times in `schedule:list`. |

---

## 6. Quick reference

| Task | Command |
|---|---|
| See all scheduled jobs and when they next run | `php artisan schedule:list` |
| Manually run whatever is currently due | `php artisan schedule:run` |
| Run the gift card job directly, right now, regardless of schedule | `php artisan gift-cards:process` |
| Simulate the scheduler loop locally (dev only) | `php artisan schedule:work` |
