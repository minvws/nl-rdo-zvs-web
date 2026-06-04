<?php

declare(strict_types=1);

namespace Tests\Feature\Enums;

use App\Enums\ProcessingStepStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepStatusTest extends FeatureTestCase
{
    #[Test]
    public function testDefaultReturnsPending(): void
    {
        $this->assertEquals(ProcessingStepStatus::PENDING, ProcessingStepStatus::default());
    }
}
