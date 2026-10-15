<?php

declare(strict_types=1);

namespace App\Collections;

use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Models\PetitionEvent;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, PetitionEvent>
 */
class PetitionEventCollection extends Collection
{
    public function finalResult(?ResultType $resultType = null): ?PetitionEvent
    {
        $finalResults = $this->where('type', PetitionEventType::FINAL_RESULT);

        if ($resultType instanceof ResultType) {
            $finalResults = $finalResults->where('result_type', $resultType);
        }

        return $finalResults->first();
    }

    public function finalResultWithoutDecision(): ?PetitionEvent
    {
        $finalResult = $this->finalResult();

        return $finalResult?->result_type === ResultType::FINAL_DECISION ? null : $finalResult;
    }
}
