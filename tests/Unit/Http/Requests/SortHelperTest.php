<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Enums\DecisionCriteria;
use App\Enums\PetitionCriteria;
use App\Enums\SortDirection;
use App\Http\Requests\SortHelper;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SortHelperTest extends TestCase
{
    #[Test]
    public function getAriaReturnsNoneWhenNoSortParameter(): void
    {
        $request = new Request();
        $sortHelper = new SortHelper($request);

        $aria = $sortHelper->getAria(DecisionCriteria::NAME);

        $this->assertEquals(SortDirection::NONE->value, $aria);
    }

    #[Test]
    public function getAriaReturnsAscendingForPositiveSort(): void
    {
        $request = new Request(['sort' => 'name']);
        $sortHelper = new SortHelper($request);

        $aria = $sortHelper->getAria(DecisionCriteria::NAME);

        $this->assertEquals(SortDirection::ASC->value, $aria);
    }

    #[Test]
    public function getAriaReturnsDescendingForNegativeSort(): void
    {
        $request = new Request(['sort' => '-name']);
        $sortHelper = new SortHelper($request);

        $aria = $sortHelper->getAria(DecisionCriteria::NAME);

        $this->assertEquals(SortDirection::DESC->value, $aria);
    }

    #[Test]
    public function getAriaWorksWithPetitionCriteria(): void
    {
        $request = new Request(['sort' => 'applicant']);
        $sortHelper = new SortHelper($request);

        $aria = $sortHelper->getAria(PetitionCriteria::APPLICANT);

        $this->assertEquals(SortDirection::ASC->value, $aria);
    }

    #[Test]
    public function getLinkCreatesAscendingSortWhenNoSort(): void
    {
        $baseUrl = $this->faker->url();
        $request = Request::create($baseUrl);
        $sortHelper = new SortHelper($request);

        $link = $sortHelper->getLink(DecisionCriteria::NAME);

        $this->assertStringContainsString('sort=name', $link);
    }

    #[Test]
    public function getLinkTogglesToDescendingWhenCurrentlyAscending(): void
    {
        $baseUrl = $this->faker->url();
        $request = Request::create($baseUrl . '?sort=name');
        $sortHelper = new SortHelper($request);

        $link = $sortHelper->getLink(DecisionCriteria::NAME);

        $this->assertStringContainsString('sort=-name', $link);
    }

    #[Test]
    public function getLinkRemovesSortWhenCurrentlyDescending(): void
    {
        $baseUrl = $this->faker->url();
        $request = Request::create($baseUrl . '?sort=-name');
        $sortHelper = new SortHelper($request);

        $link = $sortHelper->getLink(DecisionCriteria::NAME);

        $this->assertStringNotContainsString('sort=', $link);
    }

    #[Test]
    public function getLinkWorksWithPetitionCriteria(): void
    {
        $baseUrl = $this->faker->url();
        $request = Request::create($baseUrl);
        $sortHelper = new SortHelper($request);

        $link = $sortHelper->getLink(PetitionCriteria::APPLICANT);

        $this->assertStringContainsString('sort=applicant', $link);
    }
}
