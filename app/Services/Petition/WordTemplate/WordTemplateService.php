<?php

declare(strict_types=1);

namespace App\Services\Petition\WordTemplate;

use App\Enums\WordTemplateId;
use App\Repositories\WordTemplate\WordTemplateNotFoundException;
use App\Repositories\WordTemplate\WordTemplateRepositoryInterface;

class WordTemplateService
{
    public function __construct(
        private readonly WordTemplateRepositoryInterface $wordTemplateRepository,
    ) {
    }

    /**
     * @return object{
     *     id: WordTemplateId,
     *     filename: string,
     *     path: string,
     * }
     *
     * @throws WordTemplateException
     */
    public function get(WordTemplateId $id): object
    {
        try {
            return $this->wordTemplateRepository->getById($id);
        } catch (WordTemplateNotFoundException $wordTemplateNotFoundException) {
            throw new WordTemplateException($wordTemplateNotFoundException->getMessage(), 0, $wordTemplateNotFoundException);
        }
    }
}
