<?php

declare(strict_types=1);

use App\ScheduledJobs\SetPetitionTotals;
use Illuminate\Support\Facades\Schedule;

Schedule::call(SetPetitionTotals::class)
    ->timezone('Europe/Amsterdam')
    ->dailyAt('01:00')
    ->name('Set Petition Totals')
    ->withoutOverlapping();
