<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components;

use App\Enums\ContactRole;
use App\Enums\CustomCostType;
use App\Enums\CustomDateLabel;
use App\Enums\ExternalUrlType;
use App\Enums\PetitionDeliverableType;
use App\Enums\QuerysnapshotType;
use App\Enums\TermType;
use App\Enums\TimelineType;
use App\Models\Attachment;
use App\Models\Contact;
use App\Models\CustomPetitionProperty;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionType;
use App\Models\PolicyDepartment;
use App\Models\TimelineItem as TimelineItemModel;
use App\Models\User;
use App\View\Components\TimelineItem;
use Illuminate\Testing\TestComponent;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Feature\FeatureTestCase;

class TimelineItemTest extends FeatureTestCase
{
    public function testRenderAssignmentOccurrence(): void
    {
        $this->assertView(TimelineType::ASSIGNMENT_OCCURRENCE, [
            'current_assigned_user_id' => User::factory()->create()->id,
            'previous_assigned_user_id' => User::factory()->create()->id,
        ]);
    }

    public function testRenderAssignmentOccurrenceWithoutUsers(): void
    {
        $this->assertView(TimelineType::ASSIGNMENT_OCCURRENCE, [
            'current_assigned_user_id' => null,
            'previous_assigned_user_id' => null,
        ]);
    }

    public function testRenderContactAttached(): void
    {
        $this->assertView(TimelineType::CONTACT_ATTACHED, [
            'contact_id' => Contact::factory()->create()->id,
            'role' => $this->faker->randomElement(ContactRole::cases()),
        ]);
    }

    public function testRenderContactDetached(): void
    {
        $this->assertView(TimelineType::CONTACT_DETACHED, [
            'contact_id' => Contact::factory()->create()->id,
            'role' => $this->faker->randomElement(ContactRole::cases()),
        ]);
    }

    public function testRenderContactPivotUpdated(): void
    {
        $this->assertView(TimelineType::CONTACT_PIVOT_UPDATED, [
            'contact_id' => Contact::factory()->create()->id,
            'reference' => $this->faker->word(),
            'correspondence_preference' => $this->faker->word(),
        ]);
    }

    public function testRenderNote(): void
    {
        $this->assertView(TimelineType::NOTE, [
            'comment' => $this->faker->sentence(),
            'attachmentIds' => [],
        ]);
    }

    public function testRenderNoteWithAttachment(): void
    {
        $this->assertView(TimelineType::NOTE, [
            'comment' => $this->faker->sentence(),
            'attachmentIds' => [
                Attachment::factory()->create()->id,
            ],
        ]);
    }

    public function testRenderTimelineableCreated(): void
    {
        $this->assertView(TimelineType::TIMELINEABLE_CREATED);
    }

    public function testRenderTermCreated(): void
    {
        $this->assertView(TimelineType::TERM_CREATED, [
            'term_type' => $this->faker->randomElement(TermType::cases()),
        ]);
    }

    public function testRenderTermUpdated(): void
    {
        $this->assertView(TimelineType::TERM_UPDATED, [
            'term_type' => $this->faker->randomElement(TermType::cases()),
        ]);
    }

    public function testRenderTermDeleted(): void
    {
        $this->assertView(TimelineType::TERM_DELETED, [
            'term_type' => $this->faker->randomElement(TermType::cases()),
        ]);
    }

    public function testRenderDeliverableCreated(): void
    {
        $this->assertView(TimelineType::DELIVERABLE_CREATED, [
            'type' => $this->faker->randomElement(PetitionDeliverableType::cases()),
        ]);
    }

    public function testRenderDeliverableUpdated(): void
    {
        $this->assertView(TimelineType::DELIVERABLE_UPDATED, [
            'type' => $this->faker->randomElement(PetitionDeliverableType::cases()),
        ]);
    }

    public function testRenderDeliverableDeleted(): void
    {
        $this->assertView(TimelineType::DELIVERABLE_DELETED, [
            'type' => $this->faker->randomElement(PetitionDeliverableType::cases()),
        ]);
    }

    public function testRenderPolicyDepartmentChanged(): void
    {
        $policyDepartments = PolicyDepartment::factory()->count($this->faker->numberBetween(1, 5))->create();

        $policyDepartmentIds = $policyDepartments->map(static function (PolicyDepartment $policyDepartment): string {
            return $policyDepartment->id->toString();
        });

        $this->assertView(TimelineType::POLICY_DEPARTMENT_CHANGED, [
            'policy_department_ids' => $policyDepartmentIds,
        ]);
    }

    public function testRenderPetitionCustomPropertiesChanged(): void
    {
        $this->assertView(TimelineType::PETITION_CUSTOM_PROPERTIES_CHANGED, [
            'custom_petition_properties' => [],
        ]);
    }

    public function testRenderPetitionCorrespondenceChanged(): void
    {
        $this->assertView(TimelineType::CORRESPONDENCE_UPDATED, [
            'message' => $this->faker()->word(),
            'date_of_message' => $this->faker->calendarDate()->format('Y-m-d'),
        ]);
    }

    public function testRenderPetitionCustomPropertiesChangedWithData(): void
    {
        $this->assertView(TimelineType::PETITION_CUSTOM_PROPERTIES_CHANGED, [
            'custom_petition_properties' => [
                CustomPetitionProperty::factory()->create()->id,
            ],
        ]);
    }

    public function testRenderPetitionCustomDatesChanged(): void
    {
        $this->assertView(TimelineType::PETITION_CUSTOM_DATES_CHANGED, [
            'custom_dates' => [],
        ]);
    }

    public function testRenderPetitionCustomDatesChangedWithData(): void
    {
        $this->assertView(TimelineType::PETITION_CUSTOM_DATES_CHANGED, [
            'custom_dates' => [
                [
                    'date_label' => $this->faker()->randomElement(
                        CustomDateLabel::cases(),
                    )->value,
                    'date' => $this->faker->calendarDate()->format(
                        'Y-m-d',
                    ),
                ],
            ],
        ]);
    }

    public function testRenderPetitionCustomCostsChangedWithData(): void
    {
        $this->assertView(TimelineType::CUSTOM_COST_UPDATED, [
            'custom_costs' => [
                [
                    'custom_cost_type' => $this->faker->randomElement(CustomCostType::cases())->value,
                    'custom_cost_amount_in_euros' => $this->faker->numberBetween(1, 100_000_000),
                ],
            ],
        ]);
    }

    public function testRenderPetitionUpdated(): void
    {
        $this->assertView(TimelineType::PETITION_UPDATED, [
            'petition_category_id' => PetitionCategory::factory()->create()->id,
            'petition_type_id' => PetitionType::factory()->create()->id,
            'name' => $this->faker->word(),
            'date_of_entry' => $this->faker->calendarDate()->format('Y-m-d'),
            'description' => $this->faker->sentence(),
        ]);
    }

    public function testRenderDecisionUpdated(): void
    {
        $this->assertView(
            TimelineType::DECISION_UPDATED,
            [
                'name' => $this->faker->word(),
                'date' => $this->faker->calendarDate()->format('Y-m-d'),
                'reference' => $this->faker->word(),
            ],
            'decision',
        );
    }

    public function testRenderPetitionWithoutPetitionCategory(): void
    {
        $this->assertView(TimelineType::PETITION_UPDATED, [
            'petition_type_id' => PetitionType::factory()->create()->id,
            'name' => $this->faker->word(),
            'date_of_entry' => $this->faker->calendarDate()->format('Y-m-d'),
            'description' => $this->faker->sentence(),
        ]);
    }

    #[TestWith([TimelineType::PROCESSING_STEP_CREATED])]
    #[TestWith([TimelineType::PROCESSING_STEP_UPDATED])]
    #[TestWith([TimelineType::PROCESSING_STEP_DELETED])]
    public function testRenderProcessingStepCreated(TimelineType $type): void
    {
        $this->assertView($type, [
            'name' => $this->faker->word(),
            'assigned_to' => User::factory()->create()->id,
            'status' => $this->faker->word(),
            'deadline_at' => $this->faker->calendarDate()->format('Y-m-d'),
        ]);
    }

    #[TestWith([TimelineType::PROCESSING_STEP_CREATED])]
    #[TestWith([TimelineType::PROCESSING_STEP_UPDATED])]
    #[TestWith([TimelineType::PROCESSING_STEP_DELETED])]
    public function testRenderProcessingStepCreatedWithoutUser(TimelineType $type): void
    {
        $this->assertView($type, [
            'name' => $this->faker->word(),
            'assigned_to' => null,
            'status' => $this->faker->word(),
            'deadline_at' => $this->faker->calendarDate()->format('Y-m-d'),
        ]);
    }

    public function testRenderStatusOccurenceWithoutDate(): void
    {
        $this->assertView(TimelineType::STATUS_OCCURRENCE, [
            'previous_status' => null,
            'current_status' => $this->faker->word(),
        ]);
    }

    public function testRenderStatusOccurenceWithoutPreviousStatusAndDate(): void
    {
        $this->assertView(TimelineType::STATUS_OCCURRENCE, [
            'current_status' => $this->faker->word(),
        ]);
    }

    public function testRenderStatusOccurenceWithDate(): void
    {
        $this->assertView(TimelineType::STATUS_OCCURRENCE, [
            'previous_status' => null,
            'current_status' => $this->faker->word(),
            'date' => $this->faker->calendarDate()->format('Y-m-d'),
        ]);
    }

    public function testRenderPetitionArchived(): void
    {
        $this->assertView(TimelineType::PETITION_ARCHIVED);
    }

    public function testRenderDecisionArchived(): void
    {
        $this->assertView(TimelineType::DECISION_ARCHIVED);
    }

    public function testRenderDecisionUnarchived(): void
    {
        $this->assertView(TimelineType::DECISION_UNARCHIVED);
    }

    public function testRenderExternalUrlUpdated(): void
    {
        $this->assertView(TimelineType::EXTERNAL_URL_UPDATED, [
            'external_urls' => [
                [
                    'petition_external_url_type' => ExternalUrlType::PUBLICATION_PAGE->value,
                    'url' => $this->faker->url(),
                ],
                [
                    'petition_external_url_type' => ExternalUrlType::DECISION_PAGE->value,
                    'url' => $this->faker->url(),
                ],
            ],
        ]);
    }

    public function testRenderExternalUrlUpdatedEmpty(): void
    {
        $this->assertView(TimelineType::EXTERNAL_URL_UPDATED, [
            'external_urls' => [],
        ]);
    }

    public function testRenderQuerysnapshotUpdated(): void
    {
        $this->assertView(TimelineType::QUERYSNAPSHOT_UPDATED, [
            'querysnapshots' => [
                [
                    'querysnapshot_type' => QuerysnapshotType::CHAT->value,
                    'querysnapshot_id' => $this->faker->uuid(),
                ],
                [
                    'querysnapshot_type' => QuerysnapshotType::DOCUMENT->value,
                    'querysnapshot_id' => $this->faker->uuid(),
                ],
            ],
        ]);
    }

    public function testRenderQuerysnapshotUpdatedEmpty(): void
    {
        $this->assertView(TimelineType::QUERYSNAPSHOT_UPDATED, [
            'querysnapshots' => [],
        ]);
    }

    public function testRenderPetitionEventsCreated(): void
    {
        $this->assertView(TimelineType::PETITION_EVENTS_CREATED, [
            'event_types' => [
                'receipt_of_objection',
                'primary_decision',
            ],
            'count' => 2,
        ]);
    }

    public function testRenderFallbackOnException(): void
    {
        $timelineable = Petition::factory()->create();
        $timelineItem = TimelineItemModel::factory()->create([
            'timelineable_id' => $timelineable->id,
            'timelineable_type' => 'petition',
            'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
            // Force invalid data to trigger an exception in the component
            'data' => ['current_assigned_user_id' => ['invalid'], 'previous_assigned_user_id' => null],
        ])->refresh();

        $view = $this->component(TimelineItem::class, ['timelineItem' => $timelineItem]);
        $html = (string) $view->render();

        $this->assertStringContainsString('Activiteit kan niet meer gevonden worden', $html);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertView(
        TimelineType $timelineType,
        array $data = [],
        string $timelineableType = 'petition',
    ): void {
        $timelineable = $timelineableType === 'petition'
            ? Petition::factory()->create()
            : Decision::factory()->create();

        $timelineItem = TimelineItemModel::factory()
            ->create([
                'timelineable_id' => $timelineable->id,
                'timelineable_type' => $timelineableType,
                'type' => $timelineType,
                'data' => $data,
            ])
            ->refresh();

        $component = $this->component(TimelineItem::class, ['timelineItem' => $timelineItem]);

        $this->assertInstanceOf(TestComponent::class, $component);
    }
}
