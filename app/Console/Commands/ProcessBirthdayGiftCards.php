<?php

namespace App\Console\Commands;

use App\Mail\BirthdayGiftCardMail;
use App\Mail\GiftCardReminderMail;
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
    protected $description = 'Send birthday gift cards to employees when due, and remind the person in charge daily starting 7 days before if the gift card code is still empty';

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

            if ($giftCard->status === 'sent') {
                continue;
            }

            $hasCode = filled($giftCard->gift_card_code);

            if ($daysUntil === 0 && $hasCode) {
                Mail::to($employee->email)->send(new BirthdayGiftCardMail($employee, $giftCard));
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

        $this->info("Done. {$sent} gift card(s) sent, {$reminded} reminder(s) sent.");

        return self::SUCCESS;
    }
}
