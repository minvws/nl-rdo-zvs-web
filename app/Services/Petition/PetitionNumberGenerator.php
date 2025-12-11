<?php

declare(strict_types=1);

namespace App\Services\Petition;

use App\Models\Department;
use App\Models\PetitionNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

use function sprintf;

class PetitionNumberGenerator implements PetitionNumberGeneratorInterface
{
    public function generate(Department $department): string
    {
        $year = CarbonImmutable::now()->format('Y');

        $lastNumber = PetitionNumber::query()
            ->where('department_id', $department->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->first()->number ?? 0;

        $newNumber = $lastNumber + 1;

        PetitionNumber::query()->updateOrCreate([
            'department_id' => $department->id,
            'year' => $year,
        ], [
            'number' => $newNumber,
        ]);

        $number = Str::of((string) $newNumber)->padLeft(5, '0');
        $letter = Str::of($department->abbreviation)->substr(0, 1);

        return sprintf('%s%s%s', CarbonImmutable::now()->format('Y'), $letter, $number);
    }

    public function suggestNextNumber(Department $department): string
    {
        $year = CarbonImmutable::now()->format('Y');

        $lastNumber = PetitionNumber::query()
            ->where('department_id', $department->id)
            ->where('year', $year)
            ->first()->number ?? 0;

        $newNumber = $lastNumber + 1;

        $number = Str::of((string) $newNumber)->padLeft(5, '0');
        $letter = Str::of($department->abbreviation)->substr(0, 1);

        return sprintf('%s%s%s', $year, $letter, $number);
    }
}
