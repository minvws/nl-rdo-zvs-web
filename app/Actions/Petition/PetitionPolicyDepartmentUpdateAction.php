<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Throwable;
use Webmozart\Assert\Assert;

readonly class PetitionPolicyDepartmentUpdateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function execute(Petition $petition, User $user, array $attributes): void
    {
        $this->databaseManager->transaction(
            static function () use ($petition, $user, $attributes): void {
                $policyDepartmentIds = $attributes['policy_department_ids'];
                Assert::isArray($policyDepartmentIds);
                Assert::allString($policyDepartmentIds);
                Assert::allUuid($policyDepartmentIds);

                $petition->policyDepartments()->sync($policyDepartmentIds);

                $petition->timelineItems()->create([
                    'user_id' => $user->id,
                    'type' => TimelineType::POLICY_DEPARTMENT_CHANGED,
                    'data' => new ArrayObject([
                        'policy_department_ids' => $policyDepartmentIds,
                    ]),
                ]);
            },
        );
    }
}
