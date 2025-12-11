<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PetitionEvent\EventsStoreAction;
use App\Actions\PetitionEvent\PetitionEventPersistAction;
use App\Config\DepartmentConfigurationService;
use App\Enums\PetitionEventType;
use App\Enums\RouteName;
use App\Http\Requests\PetitionEvent\AddPetitionEventRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Services\PetitionEvent\PetitionEventsStorage;
use App\Services\PetitionEventAvailabilityService;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Throwable;

use function array_map;
use function to_route;

final readonly class PetitionEventWizardController
{
    public function __construct(
        private PetitionEventsStorage $eventsStorage,
        private Factory $view,
        private PetitionEventAvailabilityService $availabilityService,
        private DepartmentConfigurationService $configurationService,
    ) {
    }

    public function reset(Department $department, Petition $petition): RedirectResponse
    {
        $this->eventsStorage->clearWizard($petition);

        return to_route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
            'department' => $department,
            'petition' => $petition,
        ]);
    }

    public function start(Department $department, Petition $petition): View
    {
        $events = $this->getEvents($petition);

        $availableTypes = $this->availabilityService->getAvailableEventTypes($petition->petitionType->type, $events);

        $viewData = [
            'petition' => $petition,
            'events' => $events,
            'availableTypes' => $availableTypes,
        ];

        return $this->view->make('petition_events.partials.summary', $viewData);
    }

    public function create(Department $department, Petition $petition, PetitionEventType $type): View
    {
        $config = $this->configurationService->getEventConfiguration($department, $petition->petitionType->type, $type);

        return $this->view->make('petition_events.create', [
            'config' => $config,
            'selectedType' => $type,
            'department' => $department,
            'petition' => $petition,
        ]);
    }

    public function add(
        Department $department,
        Petition $petition,
        AddPetitionEventRequest $request,
        EventsStoreAction $action,
    ): RedirectResponse {
        $action->execute($petition, $request->toPetitionEventData());

        return to_route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
            'department' => $department,
            'petition' => $petition,
        ]);
    }

    public function deleteLast(Department $department, Petition $petition): RedirectResponse
    {
        $events = $this->eventsStorage->getWizardEvents($petition) ?? WizardEventCollection::make();

        if (!$events->isEmpty()) {
            $events = $events->removeLast();
            $this->eventsStorage->setWizardEvents($petition, $events);
        }

        return to_route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
            'department' => $department,
            'petition' => $petition,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Department $department, Petition $petition, PetitionEventPersistAction $action): RedirectResponse
    {
        $action->execute($petition);

        return to_route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]);
    }

    private function getEvents(Petition $petition): WizardEventCollection
    {
        $events = $this->eventsStorage->getWizardEvents($petition);

        if (!$events instanceof WizardEventCollection) {
            $events = $this->loadExistingEventsFromDatabase($petition);
            $this->eventsStorage->setWizardEvents($petition, $events);
        }

        return $events;
    }

    private function loadExistingEventsFromDatabase(Petition $petition): WizardEventCollection
    {
        $existingEvents = $petition->petitionEvents()->oldest()->get();

        if ($existingEvents->isEmpty()) {
            return WizardEventCollection::make();
        }

        /** @var Collection<int, PetitionEventData> $events */
        $events = $existingEvents->map(static function (PetitionEvent $event): PetitionEventData {
            /** @var array<int, PenaltyData> $penalties */
            $penalties = array_map(
                static fn(array $penalty): PenaltyData => new PenaltyData(
                    amount: (int) $penalty['amount'],
                    duration: (int) $penalty['duration'],
                ),
                $event->penalties ?? [],
            );

            return new PetitionEventData(
                type: $event->type,
                date: $event->date,
                createdAt: $event->created_at->toImmutable(),
                duration: $event->duration,
                penalties: $penalties,
                suspensionType: $event->suspension_type,
                resultType: $event->result_type,
            );
        });

        return new WizardEventCollection($events);
    }
}
