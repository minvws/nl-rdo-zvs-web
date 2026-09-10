<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;

/**
 * Import without any configuration, for reading a spreadsheet's raw rows.
 */
class RawSheetImport implements Import
{
}
