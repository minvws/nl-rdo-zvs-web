<?php

declare(strict_types=1);

namespace Tests\Smoke\Petition;

use App\Enums\PetitionTypeType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Tests\Helpers\ConfigHelper;
use Tests\Smoke\SmokeTestCase;

use function __;
use function sprintf;

class PetitionPaginationTest extends SmokeTestCase
{
    private int $itemsPerPage = 3; // Reduced from 4
    private int $totalItems = 9; // Reduced from 12
    private string $petitionPrefix = 'Petition';
    private User $user;
    private Department $department;
    private PetitionType $petitionType;
    private PetitionType $otherPetitionType;
    private PetitionStatus $petitionStatus;

    protected function setUp(): void
    {
        parent::setUp();

        ConfigHelper::set('app.pagination.items_per_page', $this->itemsPerPage);

        $this->user = User::factory()->fullyVerified()->create();
        $this->department = Department::factory()->create(['hide_column_defaults' => null]);
        $this->petitionStatus = PetitionStatus::factory()
            ->recycle($this->department)
            ->create();
        $this->petitionType = PetitionType::factory()
            ->recycle($this->department)
            ->create(['type' => PetitionTypeType::WOO_VERZOEK->value]);
        $this->otherPetitionType = PetitionType::factory()
            ->recycle($this->department)
            ->create(['type' => PetitionTypeType::BEZWAAR->value]);
        Petition::factory()
            ->count($this->totalItems)
            ->sequence(function (Sequence $sequence): array {
                // Switch between the two petition types
                $petitionType = $sequence->index % 2 === 0 ? $this->petitionType : $this->otherPetitionType;
                return [
                    'petition_type_id' => $petitionType->id,
                    'department_id' => $this->department->id,
                    'petition_status_id' => $this->petitionStatus->id,
                    'number' => sprintf('%s-%s', $this->petitionPrefix, $this->totalItems - $sequence->index), // create in reversed order
                ];
            })
            ->create();
    }

    public function testTotalCounter(): void
    {
        $this->beUser($this->user, department: $this->department)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $this->department->slug])
            ->see(__('petition.count', ['count' => $this->totalItems]));
    }

    public function testPagingNavigation(): void
    {
        $interaction = $this->beUser($this->user, department: $this->department)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $this->department->slug])
            ->see(sprintf('<h1>%s</h1>', __('petition.model_plural')));

        for ($i = $this->itemsPerPage - 1; $i > 0; $i--) {
            $interaction->see(sprintf('%s-%s', $this->petitionPrefix, $i));
        }

        $interaction->click(__('general.next'))->assertResponseOk();
        // The next page contains the items for page 2
        for ($i = $this->startIndexForPageNumber(2); $i < $this->itemsPerPage + 1; $i++) {
            $interaction->see(sprintf('%s-%s', $this->petitionPrefix, $i));
        }

        $interaction->click(__('general.last'))->assertResponseOk();
        // The final page contains the items for page 3
        for ($i = $this->startIndexForPageNumber(3); $i < $this->itemsPerPage + 1; $i++) {
            $interaction->see(sprintf('%s-%s', $this->petitionPrefix, $i));
        }

        $interaction->click(__('general.previous'))->assertResponseOk();
        // The previous page contains items for page 2
        for ($i = $this->startIndexForPageNumber(2); $i < $this->itemsPerPage + 1; $i++) {
            $interaction->see(sprintf('%s-%s', $this->petitionPrefix, $i));
        }

        $interaction->click(__('general.first'))->assertResponseOk();
        // The first page contains the items for page 1
        for ($i = $this->startIndexForPageNumber(1); $i <= $this->itemsPerPage; $i++) {
            $interaction->see(sprintf('%s-%s', $this->petitionPrefix, $i));
        }
    }

    public function testFilteringResetsPagination(): void
    {
        $this->beUser($this->user, department: $this->department)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $this->department->slug])
            ->see(sprintf('<h1>%s</h1>', __('petition.model_plural')))
            ->select($this->petitionType->id, 'filter[petition_type]')
            ->press(__('general.filter_now'))
            ->assertResponseOk()
            // The 'woo' petition type is filtered away now
            ->seeInElement('tr', $this->petitionType->name)
            ->dontSee(sprintf('<td>%s</td>', $this->otherPetitionType->name))
            // Now go to the next page
            ->click(__('general.next'))
            ->assertResponseOk()
            // Page 2 is active in pagination
            ->seeInElement('li.active.pagination__item span', '2')
            ->seeInElement('td', $this->petitionType->name)
            // The 'woo' petition type is still filtered away
            ->dontSee(sprintf('<td>%s</td>', $this->otherPetitionType->name))
            // Now when updating the filter, the paging should be reset
            ->select($this->otherPetitionType->id, 'filter[petition_type]')
            ->press(__('general.filter_now'))
            ->assertResponseOk()
            // Page 1 is active in pagination (pagination was reset)
            ->seeInElement('li.active.pagination__item span', '1');
    }

    public function testPaginationKeepsFilters(): void
    {
        $this->beUser($this->user, department: $this->department)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $this->department->slug])
            ->see(sprintf('<h1>%s</h1>', __('petition.model_plural')))
            ->select($this->petitionType->id, 'filter[petition_type]')
            ->press(__('general.filter_now'))
            ->assertResponseOk()
            ->seeInElement('td', $this->petitionType->name)
            ->dontSeeInElement('td', $this->otherPetitionType->name)
            // Now go to the next page
            ->click(__('general.next'))
            ->assertResponseOk()
            ->seeInElement('li.active.pagination__item span', '2')
            ->seeInElement('td', $this->petitionType->name)
            ->dontSeeInElement('td', $this->otherPetitionType->name)
            // Now go to the previous page
            ->click(__('general.previous'))
            ->assertResponseOk()
            ->seeInElement('li.active.pagination__item span', '1')
            ->seeInElement('td', $this->petitionType->name)
            ->dontSeeInElement('td', $this->otherPetitionType->name);
    }

    private function startIndexForPageNumber(int $pageNumber): int
    {
        return ($pageNumber - 1) * $this->itemsPerPage + 1;
    }
}
