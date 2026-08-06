<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TimelineType;
use App\Models\TimelineItem;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

#[Description('Convert legacy processing_step timeline items to use first_assignee key')]
#[Signature('app:convert-legacy-processing-step-timeline-items')]
final class ConvertLegacyProcessingStepTimelineItems extends Command
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $timelineItems = TimelineItem::query()
            ->whereIn('type', [
                TimelineType::PROCESSING_STEP_CREATED,
                TimelineType::PROCESSING_STEP_UPDATED,
                TimelineType::PROCESSING_STEP_DELETED,
            ])
            ->get();

        $this->databaseManager->transaction(static function () use ($timelineItems): void {
            $timelineItems->each(static function (TimelineItem $item): void {
                $data = $item->data;

                if (!isset($data['assigned_to']) || isset($data['first_assignee'])) {
                    return;
                }

                $data['first_assignee'] = $data['assigned_to'];
                unset($data['assigned_to']);

                $item->update(['data' => $data]);
            });
        });

        $this->info('Converted ' . $timelineItems->count() . ' timeline items.');

        return self::SUCCESS;
    }
}
