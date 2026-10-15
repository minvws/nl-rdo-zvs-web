<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Decides how an "ongespecificeerde aanhouding" affects the terms that are already running.
 *
 * Team A, B and I use the freezing behaviour; every other team stays on the term based
 * behaviour the application had before.
 */
enum UnspecifiedAdjournmentMode: string
{
    /**
     * Every open day is skipped while the objection and decision periods spend their budget, so
     * both terms (and their deadlines) slide one day forward for every day the adjournment runs.
     */
    case FREEZES_TERMS = 'freezes_terms';

    /**
     * The running terms are left alone while the adjournment is open. Ending it may start a fresh
     * decision term whose length comes from the two terms registered at the start.
     */
    case CONTINUES_TERMS = 'continues_terms';
}
