<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\PetitionExport;

use App\Actions\PetitionExport\PetitionExportDeleteAction;
use App\Models\Department;
use App\Models\PetitionExport;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class PetitionExportDeleteActionTest extends FeatureTestCase
{
    public function testExecuteDeletesExportFromDatabaseAndFileSystem(): void
    {
        $disk = 'exports';
        Storage::fake($disk);

        $department = Department::factory()->create();
        $export = PetitionExport::factory()
            ->recycle($department)
            ->create(['disk' => $disk]);

        $filename = sprintf('%s.xlsx', $export->id);
        Storage::disk($disk)->put($filename, 'test content');


        $this->assertDatabaseHas('petition_exports', [
            'id' => $export->id,
        ]);
        $this->assertTrue(Storage::disk($disk)->exists($filename));

        $action = $this->app->make(PetitionExportDeleteAction::class);
        $action->execute($export);

        $this->assertDatabaseMissing('petition_exports', [
            'id' => $export->id,
        ]);
        $this->assertFalse(Storage::disk($disk)->exists($filename));
    }

    public function testExecuteHandlesNonExistentFile(): void
    {
        $disk = 'exports';
        Storage::fake($disk);

        $filename = 'non-existent-export.xlsx';

        $department = Department::factory()->create();
        $export = PetitionExport::factory()
            ->recycle($department)
            ->create([
                'disk' => $disk,
            ]);

        $this->assertDatabaseHas('petition_exports', [
            'id' => $export->id,
        ]);
        $this->assertFalse(Storage::disk($disk)->exists($filename));

        $action = $this->app->make(PetitionExportDeleteAction::class);
        $action->execute($export);

        $this->assertDatabaseMissing('petition_exports', [
            'id' => $export->id,
        ]);
    }
}
