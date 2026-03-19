<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PetitionEventType;
use App\Enums\PetitionTypeType;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function count;

class PetitionEventTypeTest extends TestCase
{
    #[Test]
    public function testLabelReturnsBezwaarLabelForBezwaarType(): void
    {
        $label = PetitionEventType::MEETING_SCHEDULED->label(PetitionTypeType::BEZWAAR);

        $this->assertEquals('Commissiezitting bepaald', $label);
    }

    #[Test]
    public function testLabelReturnsWooLabelForWooVerzoekType(): void
    {
        $label = PetitionEventType::MEETING_SCHEDULED->label(PetitionTypeType::WOO_VERZOEK);

        $this->assertEquals('Concrete datumafspraak', $label);
    }

    #[Test]
    public function testDescriptionReturnsBezwaarDescriptionForBezwaarType(): void
    {
        $description = PetitionEventType::MEETING_SCHEDULED->description(PetitionTypeType::BEZWAAR);

        $this->assertEquals('Dit is de datum waarop gedeeld is dat er een commissiezitting gehouden zal worden.', $description);
    }

    #[Test]
    public function testDescriptionReturnsWooDescriptionForWooVerzoekType(): void
    {
        $description = PetitionEventType::MEETING_SCHEDULED->description(PetitionTypeType::WOO_VERZOEK);

        $this->assertEquals(
            'Datum die in de plaats komt van de oorspronkelijke einddatum van de beslistermijn en waarmee de indiener van het Woo-verzoek schriftelijk heeft ingestemd.',
            $description,
        );
    }

    #[Test]
    public function testLabelReturnsStandardLabelForNonMeetingScheduledEvents(): void
    {
        $labelBezwaar = PetitionEventType::PRIMARY_DECISION->label(PetitionTypeType::BEZWAAR);
        $labelWoo = PetitionEventType::PRIMARY_DECISION->label(PetitionTypeType::WOO_VERZOEK);

        $this->assertEquals('Primair besluit', $labelBezwaar);
        $this->assertEquals('Primair besluit', $labelWoo);
    }

    #[Test]
    public function testLabelReturnsStandardLabelForAdjournment(): void
    {
        $labelBezwaar = PetitionEventType::ADJOURNMENT->label(PetitionTypeType::BEZWAAR);
        $labelWoo = PetitionEventType::ADJOURNMENT->label(PetitionTypeType::WOO_VERZOEK);

        $this->assertEquals('Verdaging', $labelBezwaar);
        $this->assertEquals('Verdagingsbrief verzonden', $labelWoo);
    }

    #[Test]
    public function testLabelReturnsDefaultLabelWhenNoPetitionTypeProvided(): void
    {
        $label = PetitionEventType::MEETING_SCHEDULED->label();

        $this->assertEquals('Concrete datumafspraak of Commissiezitting', $label);
    }

    #[Test]
    public function testDescriptionReturnsDefaultDescriptionWhenNoPetitionTypeProvided(): void
    {
        $description = PetitionEventType::ADJOURNMENT->description();

        $this->assertEquals('Dit is de datum waarop er is verdaagd.', $description);
    }

    #[Test]
    public function testDescriptionFallsBackToDefaultWhenTypeSpecificTranslationMissing(): void
    {
        // ACTUAL_DISCLOSURE has no bezwaar-specific description (only default and woo_verzoek)
        // HEARING_DATE has no woo_verzoek-specific description (only default and bezwaar)
        // Both should fall back to the default description when type-specific is missing

        $finalDecisionBezwaar = PetitionEventType::ACTUAL_DISCLOSURE->description(PetitionTypeType::BEZWAAR);
        $finalDecisionDefault = PetitionEventType::ACTUAL_DISCLOSURE->description();

        $hearingDateWoo = PetitionEventType::HEARING_DATE->description(PetitionTypeType::WOO_VERZOEK);
        $hearingDateDefault = PetitionEventType::HEARING_DATE->description();

        // When type-specific translation is missing, should fall back to default
        $this->assertEquals($finalDecisionDefault, $finalDecisionBezwaar);
        $this->assertEquals('Dit is de datum waarop de werkelijke verstrekking heeft plaatsgevonden.', $finalDecisionBezwaar,);

        $this->assertEquals($hearingDateDefault, $hearingDateWoo);
        $this->assertEquals('Dit is de datum van de hoorzitting.', $hearingDateWoo);
    }

    #[Test]
    public function testIsAvailableForReturnsTrueForBezwaarEvents(): void
    {
        $this->assertTrue(PetitionEventType::PRIMARY_DECISION->isAvailableFor(PetitionTypeType::BEZWAAR));
        $this->assertFalse(PetitionEventType::PRIMARY_DECISION->isAvailableFor(PetitionTypeType::WOO_VERZOEK));
    }

    #[Test]
    public function testIsAvailableForReturnsTrueForWooEvents(): void
    {
        $this->assertTrue(PetitionEventType::PETITION_RECEIVED->isAvailableFor(PetitionTypeType::WOO_VERZOEK));
        $this->assertFalse(PetitionEventType::PETITION_RECEIVED->isAvailableFor(PetitionTypeType::BEZWAAR));
    }

    #[Test]
    public function testIsAvailableForReturnsTrueForSharedEvents(): void
    {
        $this->assertTrue(PetitionEventType::MEETING_SCHEDULED->isAvailableFor(PetitionTypeType::BEZWAAR));
        $this->assertTrue(PetitionEventType::MEETING_SCHEDULED->isAvailableFor(PetitionTypeType::WOO_VERZOEK));

        $this->assertTrue(PetitionEventType::ADJOURNMENT->isAvailableFor(PetitionTypeType::BEZWAAR));
        $this->assertTrue(PetitionEventType::ADJOURNMENT->isAvailableFor(PetitionTypeType::WOO_VERZOEK));
    }

    #[Test]
    public function testGetDependenciesForReceiptOfObjection(): void
    {
        $dependencies = PetitionEventType::RECEIPT_OF_OBJECTION->getDependencies(PetitionTypeType::BEZWAAR);

        $this->assertCount(1, $dependencies);
        $this->assertContains(PetitionEventType::PRIMARY_DECISION, $dependencies);
    }

    #[Test]
    public function testGetDependenciesForLetterOfSuspensionSent(): void
    {
        $dependencies = PetitionEventType::LETTER_OF_SUSPENSION_SENT->getDependencies(PetitionTypeType::BEZWAAR);

        $this->assertCount(1, $dependencies);
        $this->assertContains(PetitionEventType::RECEIPT_OF_OBJECTION, $dependencies);
    }

    #[Test]
    public function testGetDependenciesForSuspensionEnd(): void
    {
        $dependencies = PetitionEventType::SUSPENSION_END->getDependencies(PetitionTypeType::BEZWAAR);

        $this->assertCount(1, $dependencies);
        $this->assertContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $dependencies);
    }

    #[Test]
    public function testGetDependenciesForAppealDecisionNotTimely(): void
    {
        $dependencies = PetitionEventType::APPEAL_DECISION_NOT_TIMELY->getDependencies(PetitionTypeType::BEZWAAR);

        $this->assertCount(1, $dependencies);
        $this->assertContains(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, $dependencies);
    }

    #[Test]
    public function testGetDependenciesForReceiptAppealNotTimely(): void
    {
        $dependencies = PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY->getDependencies(PetitionTypeType::BEZWAAR);

        $this->assertCount(1, $dependencies);
        $this->assertContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $dependencies);
    }

    #[Test]
    public function testGetDependenciesForUnspecifiedAdjournmentEndBezwaar(): void
    {
        $dependencies = PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->getDependencies(PetitionTypeType::BEZWAAR);

        $this->assertCount(1, $dependencies);
        $this->assertContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT, $dependencies);
    }

    #[Test]
    public function testGetDependenciesForEventWithNoDependencies(): void
    {
        $dependencies = PetitionEventType::PRIMARY_DECISION->getDependencies(PetitionTypeType::BEZWAAR);

        $this->assertEmpty($dependencies);
    }

    #[Test]
    public function testGetConflictsForPrimaryDecisionBezwaar(): void
    {
        $conflicts = PetitionEventType::PRIMARY_DECISION->getConflicts(PetitionTypeType::BEZWAAR);

        $this->assertCount(12, $conflicts);
        $this->assertContains(PetitionEventType::RECEIPT_OF_OBJECTION, $conflicts);
        $this->assertContains(PetitionEventType::FINAL_RESULT, $conflicts);
    }

    #[Test]
    public function testGetConflictsForPrimaryDecisionWoo(): void
    {
        $conflicts = PetitionEventType::PRIMARY_DECISION->getConflicts(PetitionTypeType::WOO_VERZOEK);

        $this->assertEmpty($conflicts);
    }

    #[Test]
    public function testGetConflictsForPetitionReceived(): void
    {
        $conflicts = PetitionEventType::PETITION_RECEIVED->getConflicts(PetitionTypeType::WOO_VERZOEK);

        $this->assertGreaterThan(0, count($conflicts));
        $this->assertContains(PetitionEventType::FINAL_RESULT, $conflicts);
    }

    #[Test]
    public function testGetConflictsForLetterOfSuspensionSent(): void
    {
        $conflicts = PetitionEventType::LETTER_OF_SUSPENSION_SENT->getConflicts(PetitionTypeType::BEZWAAR);

        $this->assertGreaterThan(0, count($conflicts));
        $this->assertContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $conflicts);
        $this->assertContains(PetitionEventType::FINAL_RESULT, $conflicts);
    }

    #[Test]
    public function testGetConflictsForHearingDateBezwaar(): void
    {
        $conflicts = PetitionEventType::HEARING_DATE->getConflicts(PetitionTypeType::BEZWAAR);

        $this->assertContains(PetitionEventType::FINAL_RESULT, $conflicts);
    }

    #[Test]
    public function testGetConflictsForHearingDateWoo(): void
    {
        $conflicts = PetitionEventType::HEARING_DATE->getConflicts(PetitionTypeType::WOO_VERZOEK);

        $this->assertContains(PetitionEventType::PETITION_RECEIVED, $conflicts);
    }

    #[Test]
    public function testGetConflictsForHearingDateBeroep(): void
    {
        $conflicts = PetitionEventType::HEARING_DATE->getConflicts(PetitionTypeType::BEROEP);

        $this->assertEmpty($conflicts);
    }

    #[Test]
    public function testGetConflictsForNoticeOfDefaultWithdrawnBezwaar(): void
    {
        $conflicts = PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN->getConflicts(PetitionTypeType::BEZWAAR);

        $this->assertContains(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, $conflicts);
        $this->assertContains(PetitionEventType::FINAL_RESULT, $conflicts);
    }

    #[Test]
    public function testGetConflictsForNoticeOfDefaultWithdrawnWoo(): void
    {
        $conflicts = PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN->getConflicts(PetitionTypeType::WOO_VERZOEK);

        $this->assertContains(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, $conflicts);
        $this->assertContains(PetitionEventType::FINAL_RESULT, $conflicts);
    }

    #[Test]
    public function testGetConflictsForNoticeOfDefaultWithdrawnBeroep(): void
    {
        $conflicts = PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN->getConflicts(PetitionTypeType::BEROEP);

        $this->assertEmpty($conflicts);
    }

    #[Test]
    public function testTranslationNotFoundReturnOriginalKey(): void
    {
        Lang::setLocale('en');

        $label = PetitionEventType::ADJOURNMENT->label(PetitionTypeType::WOO_VERZOEK);

        $this->assertEquals('petition_event.woo_verzoek.label.adjournment', $label);
    }
}
