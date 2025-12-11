<?php

declare(strict_types=1);

namespace App\Services\Petition\WordTemplate;

use App\Facades\DisplayDate;
use App\Models\Contact;
use App\Models\Petition;
use App\ValueObjects\Address;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

readonly class WordTemplateReplacementsMapper
{
    /**
     * @return array<string, string>
     */
    public function map(Petition $petition): array
    {
        $replacements = [
            'ACT_datum' => DisplayDate::date(CarbonImmutable::now()),
            'COR_ANH_regel1' => 'Geachte',
            'COR_datum' => DisplayDate::date(CarbonImmutable::now()),
            'DOS_naam' => $petition->name,
            'DOS_nummer' => $petition->number,
            'COR_onsKenmerk' => $petition->number,
        ];

        $contact = $this->determineContactAddress($petition);
        if ($contact instanceof Contact) {
            $replacements['COR_ADR_regel1'] = Str::address(Address::fromContact($contact));
        }
        if ($petition->decision_reference !== null) {
            $replacements['BEZ_kenmerk_besluit'] = $petition->decision_reference;
        }

        if ($petition->decision_date !== null) {
            $replacements['DOS_dtBesluit'] = DisplayDate::date($petition->decision_date);
        }

        if ($petition->message !== null) {
            $replacements['BEZ_kenmerk_gemachtigde'] = $petition->message;
        }

        if ($petition->date_of_message !== null) {
            $replacements['BEZ_dtBezwaar'] = DisplayDate::date($petition->date_of_message);
        }

        if ($petition->date_of_message !== null && $petition->message !== null) {
            $replacements['BEZ_dtOntvangst'] = DisplayDate::date($petition->date_of_message);
        }

        if ($petition->policyDepartments->isNotEmpty()) {
            $policyDepartments = $petition->policyDepartments->toString();

            $replacements['BZ_BEZ_postkamer'] = $policyDepartments;
        }

        return $replacements;
    }

    private function determineContactAddress(Petition $petition): ?Contact
    {
        if ($petition->representative->first() !== null) {
            return $petition->representative->first();
        }

        return $petition->applicant->first();
    }
}
