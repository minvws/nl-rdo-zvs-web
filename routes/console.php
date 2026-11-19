<?php

declare(strict_types=1);

use App\ScheduledJobs\EvaluateNotificationRules;
use App\ScheduledJobs\SetPetitionTotals;
use Illuminate\Support\Facades\Schedule;

Schedule::call(SetPetitionTotals::class)
    ->timezone('Europe/Amsterdam')
    ->dailyAt('01:00')
    ->name('Set Petition Totals')
    ->withoutOverlapping();

Schedule::call(EvaluateNotificationRules::class)
    ->timezone('Europe/Amsterdam')
    ->dailyAt('07:00')
    ->name('Evaluate Notification Rules')
    ->withoutOverlapping();
