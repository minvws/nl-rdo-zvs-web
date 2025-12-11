<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Virusscanner;

use App\Services\Virusscanner\ClamavVirusscanner;
use App\Services\Virusscanner\FakeVirusscanner;
use App\Services\Virusscanner\VirusscannerManager;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

class VirusscannerManagerTest extends FeatureTestCase
{
    public function testFakeInstance(): void
    {
        ConfigHelper::set('virusscanner.default', 'fake');

        /** @var VirusscannerManager $virusscannerManager */
        $virusscannerManager = $this->app->get(VirusscannerManager::class);
        $virusscanner = $virusscannerManager->driver();

        $this->assertInstanceOf(FakeVirusscanner::class, $virusscanner);
    }

    public function testClamavInstance(): void
    {
        ConfigHelper::set('virusscanner.default', 'clamav');

        /** @var VirusscannerManager $virusscannerManager */
        $virusscannerManager = $this->app->get(VirusscannerManager::class);
        $virusscanner = $virusscannerManager->driver();

        $this->assertInstanceOf(ClamavVirusscanner::class, $virusscanner);
    }
}
