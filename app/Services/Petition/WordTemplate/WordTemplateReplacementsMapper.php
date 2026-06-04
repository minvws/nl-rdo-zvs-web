<?php

declare(strict_types=1);

namespace App\Services\Petition\WordTemplate;

use App\Enums\CustomDateLabel;
use App\Facades\DisplayDate;
use App\Models\Contact;
use App\Models\Petition;
use App\ValueObjects\Address;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

use function array_filter;

readonly class WordTemplateReplacementsMapper
{
    /**
     * @return array<string, string>
     */
    public function map(Petition $petition): array
    {
        $applicant = $petition->applicant->first();
        $contact = $petition->representative->first() ?? $applicant;

        $replacements = [
            'BELEIDSDIRECTIE' => $this->mapPolicyDepartment($petition),
            'DATUM_BESLISSING_OP_BEZWAAR' => $this->mapDate(
                $petition->customDates->getByLabel(CustomDateLabel::DATE_DECISION_ON_APPEAL)?->date,
                'DATUM_BESLISSING_OP_BEZWAAR',
            ),
            'DATUM_BESTREDEN_BESLUIT' => $this->mapDate($petition->decision_date, 'DATUM_BESTREDEN_BESLUIT'),
            'DATUM_ONTVANGEN_BERICHT' => $this->mapDate($petition->date_of_message, 'DATUM_ONTVANGEN_BERICHT'),
            'EMAIL_ADRES' => $contact instanceof Contact ? $contact->email_address : 'EMAIL_ADRES',
            'KENMERK_BESTREDEN_BESLUIT' => $petition->decision_reference ?? 'KENMERK_BESTREDEN_BESLUIT',
            'KENMERK_ONTVANGEN_BERICHT' => $petition->message ?? 'KENMERK_ONTVANGEN_BERICHT',
            'KENMERK_ZVS_NUMMER' => $petition->number ?? 'KENMERK_ZVS_NUMMER',
            'NAAM_ADRES' => $contact instanceof Contact ? Str::address(Address::fromContact($contact)) : 'NAAM_ADRES',
            'NAAM_BEHANDELAAR' => $this->mapAssignedUser($petition),
            'NAAM_BEZWAARDE' => $applicant instanceof Contact ? $applicant->full_name : 'NAAM_BEZWAARDE',
            'NAAM_CONTACT' => $contact instanceof Contact ? $contact->full_name : 'NAAM_CONTACT',
            'NAAM_ZAAK' => $petition->name ?? 'NAAM_ZAAK',
            'TELEFOON_CONTACT' => $contact instanceof Contact ? $contact->phone_number : 'TELEFOON_CONTACT',
            'VANDAAG' => DisplayDate::date(CarbonImmutable::now()),
        ];

        return array_filter($replacements, static fn($value): bool => $value !== null);
    }

    private function mapDate(?CalendarDate $date, string $placeholder): string
    {
        return $date instanceof CalendarDate ? DisplayDate::date($date) : $placeholder;
    }

    private function mapPolicyDepartment(Petition $petition): string
    {
        $policyDepartment = $petition->policyDepartments()->first();

        return $policyDepartment->name ?? 'BELEIDSDIRECTIE';
    }

    private function mapAssignedUser(Petition $petition): string
    {
        $assignedUser = $petition->firstAssignee?->user;

        return $assignedUser->name ?? 'NAAM_BEHANDELAAR';
    }
}
