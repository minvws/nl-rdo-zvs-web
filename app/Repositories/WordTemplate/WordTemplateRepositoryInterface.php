<?php

declare(strict_types=1);

namespace App\Repositories\WordTemplate;

use App\Enums\WordTemplateId;

interface WordTemplateRepositoryInterface
{
    /**
     * @return object{
     *     id: WordTemplateId,
     *     filename: string,
     *     path: string,
     * }
     *
     * @throws WordTemplateNotFoundException
     */
    public function getById(WordTemplateId $id): object;
}
