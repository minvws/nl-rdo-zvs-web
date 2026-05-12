<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\QuerysnapshotUpdateAction;
use App\Enums\QuerysnapshotType;
use App\Models\Petition;
use App\Models\PetitionQuerysnapshot;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function fake;

class QuerysnapshotUpdateActionTest extends FeatureTestCase
{
    #[Test]
    public function testExecuteUpdatesExistingQuerysnapshotInsteadOfCreatingNew(): void
    {
        $petition = Petition::factory()->create();
        $oldDocId = fake()->uuid();
        $newDocId = fake()->uuid();

        $existingDocument = PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT,
                'querysnapshot_id' => $oldDocId,
            ]);

        $existingId = $existingDocument->id;

        $action = $this->app->get(QuerysnapshotUpdateAction::class);
        $action->execute($petition, new User(), [
            'querysnapshots' => [
                ['querysnapshot_type' => QuerysnapshotType::DOCUMENT->value, 'querysnapshot_id' => $newDocId],
            ],
        ]);

        $petition->refresh();
        $querysnapshots = $petition->querysnapshots()->get();

        $this->assertSame(1, $querysnapshots->count());
        $this->assertEquals($existingId, $querysnapshots->first()->id);
        $this->assertSame($newDocId, $querysnapshots->first()->querysnapshot_id);
    }

    #[Test]
    public function testExecuteDeletesRemovedTypes(): void
    {
        $petition = Petition::factory()->create();
        $docId = fake()->uuid();
        $chatId = fake()->uuid();

        PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT,
                'querysnapshot_id' => $docId,
            ]);
        PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::CHAT,
                'querysnapshot_id' => $chatId,
            ]);

        $action = $this->app->get(QuerysnapshotUpdateAction::class);
        $action->execute($petition, new User(), [
            'querysnapshots' => [
                ['querysnapshot_type' => QuerysnapshotType::DOCUMENT->value, 'querysnapshot_id' => $docId],
            ],
        ]);

        $petition->refresh();
        $querysnapshots = $petition->querysnapshots()->get();

        $this->assertSame(1, $querysnapshots->count());
        $this->assertTrue($querysnapshots->contains('querysnapshot_type', QuerysnapshotType::DOCUMENT));
    }

    #[Test]
    public function testExecuteCreatesNewForNewTypes(): void
    {
        $petition = Petition::factory()->create();
        $docId = fake()->uuid();
        $newChatId = fake()->uuid();

        PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT,
                'querysnapshot_id' => $docId,
            ]);

        $action = $this->app->get(QuerysnapshotUpdateAction::class);
        $action->execute($petition, new User(), [
            'querysnapshots' => [
                ['querysnapshot_type' => QuerysnapshotType::DOCUMENT->value, 'querysnapshot_id' => $docId],
                ['querysnapshot_type' => QuerysnapshotType::CHAT->value, 'querysnapshot_id' => $newChatId],
            ],
        ]);

        $petition->refresh();
        $querysnapshots = $petition->querysnapshots()->get();

        $this->assertSame(2, $querysnapshots->count());
    }

    #[Test]
    public function testExecuteWithEmptyArrayDeletesAll(): void
    {
        $petition = Petition::factory()->create();
        $docId = fake()->uuid();

        PetitionQuerysnapshot::factory()
            ->for($petition)
            ->create([
                'querysnapshot_type' => QuerysnapshotType::DOCUMENT,
                'querysnapshot_id' => $docId,
            ]);

        $action = $this->app->get(QuerysnapshotUpdateAction::class);
        $action->execute($petition, new User(), [
            'querysnapshots' => [],
        ]);

        $petition->refresh();

        $this->assertSame(0, $petition->querysnapshots()->count());
    }
}
