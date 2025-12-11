<?php

declare(strict_types=1);

namespace App\Services\Authorisation;

use App\Exception\AppException;
use Illuminate\Contracts\Session\Session;
use Webmozart\Assert\Assert;

use function sprintf;

class SessionHelper implements StateStorageHelperInterface
{
    private const string ACTIVE_DEPARTMENT_SESSION_KEY = 'active_department';

    public function __construct(
        private readonly Session $session,
    ) {
    }

    public function storeDepartmentSlug(string $slug): void
    {
        $this->session->put(self::ACTIVE_DEPARTMENT_SESSION_KEY, $slug);
    }

    /**
     * @throws AppException
     */
    public function getDepartmentSlug(): string
    {
        $slug = $this->session->get(self::ACTIVE_DEPARTMENT_SESSION_KEY);
        if ($slug === null) {
            throw new AppException(
                sprintf(
                    'Unable to find the selected department slug (identified by "%s") in the session',
                    self::ACTIVE_DEPARTMENT_SESSION_KEY,
                ),
            );
        }
        Assert::stringNotEmpty($slug, 'The department slug (stored in session) must be a non-empty string.');

        return $slug;
    }
}
