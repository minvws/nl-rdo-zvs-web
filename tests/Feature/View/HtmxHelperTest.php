<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\Models\Petition;
use App\View\HtmxHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\ViewErrorBag;
use Tests\Feature\FeatureTestCase;

use function __;
use function sprintf;

class HtmxHelperTest extends FeatureTestCase
{
    public function testMakeFormViewOrFragmentReturnsFormInLayout(): void
    {
        $response = $this->getHtmxHelper()
            ->makeFormViewResponse(Request::create($this->faker->word()), 'petition.assign-primary.edit', [
                'petition' => Petition::factory()->create(),
                'users' => new Collection(),
                'errors' => new ViewErrorBag(),
            ]);

        $this->assertStringContainsString(__('petition.assigned_user'), $response->getContent());
    }

    public function testMakeFormViewReturnsFormOnly(): void
    {
        $viewName = 'petition.assign-primary.edit';
        $response = $this->getHtmxHelper()->makeFormViewResponse($this->createRequestWithHtmxHeader(), $viewName, [
            'petition' => Petition::factory()->create(),
            'users' => new Collection(),
            'errors' => new ViewErrorBag(),
        ]);

        $this->assertStringContainsString(__('petition.assigned_user'), $response->getContent());
    }

    public function testIsHtmxRequest(): void
    {
        $request = $this->createRequestWithHtmxHeader();
        $result = $this->getHtmxHelper()->isHtmxRequest($request);

        $this->assertTrue($result);
    }

    public function testIsNotHtmxRequest(): void
    {
        $request = Request::create($this->faker->word());
        $result = $this->getHtmxHelper()->isHtmxRequest($request);

        $this->assertFalse($result);
    }

    private function getHtmxHelper(): HtmxHelper
    {
        /** @var HtmxHelper $htmxHelper */
        $htmxHelper = $this->app->get(HtmxHelper::class);

        return $htmxHelper;
    }

    private function createRequestWithHtmxHeader(): Request
    {
        return Request::create($this->faker->word(), server: [sprintf('HTTP_%s', HtmxHelper::HTMX_REQUEST_HEADER) => true]);
    }
}
