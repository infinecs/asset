<?php

namespace App\Console\Commands;

use App\Mail\BirthdayGiftCardMail;
use App\Mail\GiftCardReminderMail;
use App\Mail\ManagerBirthdayReminderMail;
use App\Models\Employee;
use App\Models\GiftCard;
use App\Models\GiftCardSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProcessBirthdayGiftCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gift-cards:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send birthday gift cards to employees when due, remind the person in charge daily starting 7 days before if the gift card code is still empty, and notify managers 3 and 2 days before a direct report\'s birthday';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();
        $pics = GiftCardSetting::current()->personsInCharge;

        $employees = Employee::where('status', 'active')
            ->whereNotNull('date_of_birth')
            ->where('gift_card_opt_out', false)
            ->get();

        $sent = 0;
        $reminded = 0;
        $managersNotified = 0;

        foreach ($employees as $employee) {
            $occurrence = $employee->nextBirthdayOccurrence();
            if (!$occurrence) {
                continue;
            }

            $daysUntil = (int) $today->diffInDays($occurrence);
            if ($daysUntil > 7) {
                continue;
            }

            $giftCard = GiftCard::firstOrNew([
                'employee_id' => $employee->id,
                'occasion_year' => $occurrence->year,
            ]);

            if (!$giftCard->exists) {
                $giftCard->birthday_date = $occurrence;
                $giftCard->status = 'pending';
                $giftCard->reminder_count = 0;
            }

            if ($this->notifyManagerOfUpcomingBirthday($employee, $giftCard, $daysUntil)) {
                $managersNotified++;
            }

            if ($giftCard->status === 'sent') {
                continue;
            }

            $hasCode = filled($giftCard->gift_card_code);

            if ($daysUntil === 0 && $hasCode) {
                $picEmails = $pics->pluck('email')->filter()->values()->all();
                $mail = Mail::to($employee->email);
                if (!empty($picEmails)) {
                    $mail->cc($picEmails);
                }
                $mail->send(new BirthdayGiftCardMail($employee, $giftCard));
                $giftCard->status = 'sent';
                $giftCard->sent_at = now();
                $giftCard->save();
                $sent++;
                $this->info("Sent gift card to {$employee->name} ({$employee->email}).");
                continue;
            }

            if ($hasCode) {
                $giftCard->status = 'ready';
                $giftCard->save();
                continue;
            }

            // Gift card code still empty within 7 days of (or on) the birthday — remind the PIC.
            $giftCard->status = 'reminding';
            $giftCard->reminder_count++;
            $giftCard->last_reminded_at = now();
            $giftCard->save();

            if ($pics->isNotEmpty()) {
                foreach ($pics as $pic) {
                    if ($pic->email) {
                        Mail::to($pic->email)->send(new GiftCardReminderMail($employee, $giftCard, $daysUntil));
                    }
                }
                $reminded++;
            } else {
                $this->warn("No person in charge configured — skipped reminder for {$employee->name}.");
            }
        }

        $this->info("Done. {$sent} gift card(s) sent, {$reminded} reminder(s) sent, {$managersNotified} manager notification(s) sent.");

        return self::SUCCESS;
    }

    /**
     * Notify an employee's manager 3 days and again 2 days before the employee's birthday.
     * Tracked on the gift card row so each checkpoint is only sent once per occasion.
     */
    private function notifyManagerOfUpcomingBirthday(Employee $employee, GiftCard $giftCard, int $daysUntil): bool
    {
        if (!in_array($daysUntil, [3, 2], true)) {
            return false;
        }

        $column = $daysUntil === 3 ? 'manager_notified_3d_at' : 'manager_notified_2d_at';
        if ($giftCard->{$column}) {
            return false;
        }

        $manager = $employee->manager;
        if (!$manager || !$manager->email) {
            return false;
        }

        Mail::to($manager->email)->send(new ManagerBirthdayReminderMail($employee, $manager, $daysUntil));
        $giftCard->{$column} = now();
        $giftCard->save();

        $this->info("Notified {$manager->name} that {$employee->name}'s birthday is in {$daysUntil} day(s).");

        return true;
    }
}
