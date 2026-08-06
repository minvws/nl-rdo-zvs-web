<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PetitionEvent\EventsStoreAction;
use App\Actions\PetitionEvent\PetitionEventPersistAction;
use App\Config\DepartmentConfigurationService;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Enums\RouteName;
use App\Factories\WizardEventCollectionFactory;
use App\Http\Requests\PetitionEvent\AddPetitionEventRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Services\PetitionEvent\PetitionEventsStorage;
use App\Services\PetitionEventAvailabilityService;
use App\ValueObjects\WizardEventCollection;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;
use Webmozart\Assert\Assert;

use function auth;
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
            'resultTypeLabels' => $this->resultTypeLabels($department),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function resultTypeLabels(Department $department): array
    {
        $labels = [];

        foreach (ResultType::cases() as $resultType) {
            $labels[$resultType->value] = $this->configurationService->resultTypeLabel($department, $resultType);
        }

        return $labels;
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
        $user = auth()->user();
        Assert::notNull($user);

        $action->execute($petition, $user);

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

        return WizardEventCollectionFactory::fromModels($existingEvents);
    }
}
