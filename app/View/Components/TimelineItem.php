<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PolicyDepartmentCollection;
use App\Enums\TimelineType;
use App\Models\Attachment;
use App\Models\Contact;
use App\Models\CustomPetitionProperty;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PolicyDepartment;
use App\Models\TimelineItem as TimelineItemModel;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;
use Illuminate\View\Factory;
use Throwable;
use Webmozart\Assert\Assert;

use function __;
use function array_key_exists;
use function array_map;
use function sprintf;

class TimelineItem extends Component
{
    public function __construct(
        private readonly TimelineItemModel $timelineItem,
        private readonly Factory $view,
    ) {
    }

    public function render(): ?View
    {
        try {
            return match ($this->timelineItem->type) {
                TimelineType::ASSIGNMENT_OCCURRENCE => $this->renderAssignmentOccurrence(),
                TimelineType::CONTACT_ATTACHED => $this->renderContactAttached(),
                TimelineType::CONTACT_DETACHED => $this->renderContactDetached(),
                TimelineType::CONTACT_PIVOT_UPDATED => $this->renderContactPivotUpdated(),
                TimelineType::NOTE => $this->renderNote(),
                TimelineType::TIMELINEABLE_CREATED => $this->renderView('petition.timeline.timelineable_created'),
                TimelineType::TERM_CREATED => $this->renderView('petition.timeline.term_created'),
                TimelineType::TERM_UPDATED => $this->renderView('petition.timeline.term_updated'),
                TimelineType::TERM_DELETED => $this->renderView('petition.timeline.term_deleted'),
                TimelineType::REFERENCED_OCCURRENCE => $this->renderView('petition.timeline.referenced_occurrence'),
                TimelineType::STATUS_OCCURRENCE => $this->renderStatusOccurence(),
                TimelineType::POLICY_DEPARTMENT_CHANGED => $this->renderPolicyDepartmentChanged(),
                TimelineType::PETITION_CUSTOM_PROPERTIES_CHANGED => $this->renderPetitionCustomPropertiesChanged(),
                TimelineType::PETITION_CUSTOM_DATES_CHANGED => $this->renderPetitionCustomDatesChanged(),
                TimelineType::PETITION_UPDATED => $this->renderPetitionUpdated(),
                TimelineType::DECISION_UPDATED => $this->renderDecisionUpdated(),
                TimelineType::CUSTOM_COST_UPDATED => $this->renderCustomCostsUpdated(),
                TimelineType::CORRESPONDENCE_UPDATED => $this->renderCorrespondenceUpdated(),
                TimelineType::DELIVERABLE_CREATED => $this->renderView('petition.timeline.deliverable_created'),
                TimelineType::DELIVERABLE_UPDATED => $this->renderView('petition.timeline.deliverable_updated'),
                TimelineType::DELIVERABLE_DELETED => $this->renderView('petition.timeline.deliverable_deleted'),
                TimelineType::PROCESSING_STEP_CREATED => $this->renderProcessingStepView('decision.timeline.processing_step_created'),
                TimelineType::PROCESSING_STEP_UPDATED => $this->renderProcessingStepView('decision.timeline.processing_step_updated'),
                TimelineType::PROCESSING_STEP_DELETED => $this->renderProcessingStepView('decision.timeline.processing_step_deleted'),
                TimelineType::PETITION_ARCHIVED => $this->renderView('petition.timeline.petition_archived'),
                TimelineType::DECISION_ARCHIVED => $this->renderView('decision.timeline.decision_archived'),
                TimelineType::DECISION_UNARCHIVED => $this->renderView('decision.timeline.decision_unarchived'),
                TimelineType::EXTERNAL_URL_UPDATED => $this->renderExternalUrlUpdated(),
                TimelineType::QUERYSNAPSHOT_UPDATED => $this->renderQuerysnapshotUpdated(),
                default => null,
            };
        } catch (Throwable $e) {
            Log::error('TimelineItem rendering failed', [
                'id' => $this->timelineItem->internal_id,
                'exception' => $e,
            ]);

            return $this->view->make('components.timeline-item-fallback', [
                'id' => $this->timelineItem->internal_id,
                'type' => $this->timelineItem->type,
            ]);
        }
    }

    private function renderView(string $view): View
    {
        return $this->view
            ->make($view)
            ->with([
                'timelineItem' => $this->timelineItem,
            ]);
    }

    private function renderAssignmentOccurrence(): View
    {
        return $this->view
            ->make('petition.timeline.assignment_occurrence')
            ->with([
                'timelineItem' => $this->timelineItem,
                'currentAssignedUser' => User::query()->find($this->timelineItem->data->current_assigned_user_id),
                'previousAssignedUser' => User::query()->find($this->timelineItem->data->previous_assigned_user_id),
            ]);
    }

    private function renderContactAttached(): View
    {
        return $this->view
            ->make('petition.timeline.contact_attached')
            ->with([
                'timelineItem' => $this->timelineItem,
                'contact' => Contact::query()->findSole($this->timelineItem->data->contact_id),
            ]);
    }

    private function renderContactDetached(): View
    {
        return $this->view
            ->make('petition.timeline.contact_detached')
            ->with([
                'timelineItem' => $this->timelineItem,
                'contact' => Contact::query()->findSole($this->timelineItem->data->contact_id),
            ]);
    }

    private function renderContactPivotUpdated(): View
    {
        $data = (array) $this->timelineItem->data;
        Assert::keyExists($data, 'contact_id');
        Assert::string($data['contact_id']);

        return $this->view
            ->make('petition.timeline.contact_pivot_updated')
            ->with([
                'timelineItem' => $this->timelineItem,
                'contact' => Contact::query()->findSole($data['contact_id']),
            ]);
    }

    private function renderNote(): View
    {
        return $this->view
            ->make('petition.timeline.note')
            ->with([
                'timelineItem' => $this->timelineItem,
                'attachments' => Attachment::query()->whereIn('id', $this->timelineItem->data->attachmentIds)->get(),
            ]);
    }

    private function renderPolicyDepartmentChanged(): View
    {
        /** @var PolicyDepartmentCollection $policyDepartments */
        $policyDepartments = PolicyDepartment::query()->whereIn('id', $this->timelineItem->data->policy_department_ids)->get();

        return $this->view
            ->make('petition.timeline.policy_department_changed')
            ->with([
                'timelineItem' => $this->timelineItem,
                'policyDepartments' => $policyDepartments->toString(),
            ]);
    }

    private function renderPetitionCustomPropertiesChanged(): View
    {
        /** @var CustomPetitionPropertyCollection $customProperties */
        $customProperties = CustomPetitionProperty::query()->whereIn('id', $this->timelineItem->data->custom_petition_properties)->get();

        $customPropertiesString = $customProperties->isNotEmpty()
            ? sprintf('%s %s', $customProperties->toString(), __('timeline.checked'))
            : __('timeline.none_checked');

        return $this->view
            ->make('petition.timeline.petition_custom_properties_changed')
            ->with([
                'timelineItem' => $this->timelineItem,
                'customProperties' => $customPropertiesString,
            ]);
    }

    private function renderPetitionCustomDatesChanged(): View
    {
        $customDatesCollection = new Collection($this->timelineItem->data->custom_dates);
        $customDatesCollection->filter(static function (mixed $item): bool {
            return $item['date'] !== null;
        });

        return $this->view
            ->make('petition.timeline.petition_custom_dates_changed')
            ->with([
                'timelineItem' => $this->timelineItem,
                'customDatesCollection' => $customDatesCollection,
            ]);
    }

    private function renderPetitionUpdated(): View
    {
        $data = (array) $this->timelineItem->data;

        $petition = $this->timelineItem->timelineable;
        Assert::isInstanceOf($petition, Petition::class);

        $petitionCategoryId = $data['petition_category_id'] ?? null;
        /** @var PetitionCategory|null $petitionCategory */
        $petitionCategory = $petitionCategoryId ? PetitionCategory::query()->find($petitionCategoryId) : null;

        return $this->view
            ->make('petition.timeline.petition_updated')
            ->with([
                'timelineItem' => $this->timelineItem,
                'petition' => $petition,
                'petitionCategory' => $petitionCategory,
            ]);
    }

    private function renderDecisionUpdated(): View
    {
        $data = (array) $this->timelineItem->data;

        $decision = $this->timelineItem->timelineable;
        Assert::isInstanceOf($decision, Decision::class);

        return $this->view
            ->make('decision.timeline.decision_updated')
            ->with([
                'timelineItem' => $this->timelineItem,
                'decision' => $decision,
                'data' => $data,
            ]);
    }

    private function renderCustomCostsUpdated(): View
    {
        return $this->view
            ->make('petition.timeline.petition_custom_costs_changed')
            ->with([
                'timelineItem' => $this->timelineItem,
                'customCostsCollection' => new Collection($this->timelineItem->data->custom_costs),
            ]);
    }

    private function renderCorrespondenceUpdated(): View
    {
        $data = (array) $this->timelineItem->data;
        Assert::keyExists($data, 'date_of_message');
        Assert::string($data['date_of_message']);
        $date = CalendarDate::create($data['date_of_message']);

        return $this->view
            ->make('petition.timeline.correspondence_updated')
            ->with([
                'timelineItem' => $this->timelineItem,
                'date' => $date,
            ]);
    }

    private function renderProcessingStepView(string $view): View
    {
        $data = (array) $this->timelineItem->data;
        Assert::keyExists($data, 'deadline_at');
        Assert::keyExists($data, 'assigned_to');
        Assert::keyExists($data, 'status');
        Assert::keyExists($data, 'name');

        if ($data['assigned_to'] !== null) {
            Assert::string($data['assigned_to']);
            $data['assigned_to'] = User::query()->findSole($data['assigned_to']);
        }

        if ($data['deadline_at'] !== null) {
            Assert::string($data['deadline_at']);
            $data['deadline_at'] = CalendarDate::create($data['deadline_at']);
        }

        return $this->view
            ->make($view)
            ->with([
                'timelineItem' => $this->timelineItem,
                'data' => $data,
            ]);
    }

    private function renderStatusOccurence(): View
    {
        $data = (array) $this->timelineItem->data;
        Assert::keyExists($data, 'current_status');
        Assert::string($data['current_status']);

        if (array_key_exists('comment', $data)) {
            Assert::nullOrStringNotEmpty($data['comment']);
            $comment = $data['comment'];
        }

        $date = $this->getStatusOccurenceDate($data);

        return $this->view
            ->make('petition.timeline.status_occurrence')
            ->with([
                'timelineItem' => $this->timelineItem,
                'currentStatus' => $data['current_status'],
                'date' => $date,
                'comment' => $comment ?? null,
            ]);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function getStatusOccurenceDate(array $data): CalendarDate
    {
        if (array_key_exists('date', $data)) {
            Assert::string($data['date']);

            return CalendarDate::create($data['date']);
        }

        return CalendarDate::instance($this->timelineItem->created_at);
    }

    private function renderExternalUrlUpdated(): View
    {
        $data = (array) $this->timelineItem->data;
        Assert::isArray($data['external_urls']);

        $externalUrls = array_map(static function (mixed $externalUrl): array {
            Assert::isArray($externalUrl);
            Assert::nullOrString($externalUrl['petition_external_url_type']);
            Assert::nullOrString($externalUrl['url']);

            return [
                'petition_external_url_type' => $externalUrl['petition_external_url_type'],
                'url' => $externalUrl['url'],
            ];
        }, $data['external_urls']);

        return $this->view
            ->make('petition.timeline.external_url_updated')
            ->with([
                'timelineItem' => $this->timelineItem,
                'externalUrls' => $externalUrls,
            ]);
    }

    private function renderQuerysnapshotUpdated(): View
    {
        $data = (array) $this->timelineItem->data;
        Assert::isArray($data['querysnapshots']);

        $querysnapshots = array_map(static function (mixed $querysnapshot): array {
            Assert::isArray($querysnapshot);
            Assert::nullOrString($querysnapshot['querysnapshot_type']);
            Assert::nullOrString($querysnapshot['querysnapshot_id']);

            return [
                'querysnapshot_type' => $querysnapshot['querysnapshot_type'],
                'querysnapshot_id' => $querysnapshot['querysnapshot_id'],
            ];
        }, $data['querysnapshots']);

        return $this->view
            ->make('petition.timeline.querysnapshot_updated')
            ->with([
                'timelineItem' => $this->timelineItem,
                'querysnapshots' => $querysnapshots,
            ]);
    }
}
