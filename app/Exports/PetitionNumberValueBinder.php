<?php

declare(strict_types=1);

namespace App\Exports;

use Override;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Exception;

use function is_string;

class PetitionNumberValueBinder extends DefaultValueBinder
{
    /**
     * @throws Exception
     */
    #[Override]
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getColumn() === 'A' && is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
