<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests;

use App\Enums\PetitionCriteria;
use App\Http\Requests\SortHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class SortHelperTest extends FeatureTestCase
{
    /**
     * @param array<string, string|array> $query
     */
    #[DataProvider('getAriaDataProvider')]
    public function testGetAria(array $query, string $sort, string $expectedResult): void
    {
        $sortHelper = new SortHelper(new Request($query));
        $result = $sortHelper->getAria(PetitionCriteria::from($sort));

        $this->assertEquals($expectedResult, $result);
    }

    public static function getAriaDataProvider(): array
    {
        return [
            [[], 'deadline_at', 'none'],
            [[[]], 'deadline_at', 'none'],
            [['deadline_at' => 'bar'], 'deadline_at', 'none'],
            [['deadline_at', 'sort'], 'deadline_at', 'none'],
            [['sort' => 'assigned_user'], 'assigned_user', 'ascending'],
            [['sort' => 'bar'], 'deadline_at', 'none'],
            [['sort' => '-petition_type'], 'petition_type', 'descending'],
            [['sort' => ['bar']], 'deadline_at', 'none'],
        ];
    }

    /**
     * @param array<string, string> $currentRequestParameters
     */
    #[DataProvider('getLinkDataProvider')]
    public function testGetLink(array $currentRequestParameters, string $parameter, string $expectedResult): void
    {
        $sortHelper = new SortHelper(new Request($currentRequestParameters));
        $result = $sortHelper->getLink(PetitionCriteria::from($parameter));

        $actualResult = Str::of($result)->after('http://:');
        $this->assertEquals($expectedResult, $actualResult);
    }

    public static function getLinkDataProvider(): array
    {
        return [
            [[], 'deadline_at', '/?sort=deadline_at'],
            [['sort' => 'deadline_at'], 'deadline_at', '/?sort=-deadline_at'],
            [['sort' => 'assigned_user'], 'deadline_at', '/?sort=deadline_at'],
            [['sort' => '-deadline_at'], 'deadline_at', ''],
            [['sort' => '-petition_type'], 'deadline_at', '/?sort=deadline_at'],
            [['sort' => ['deadline_at', 'bar']], 'deadline_at', '/?sort=deadline_at'],
            [['sort' => 'deadline_at', 'filter' => 'bar'], 'deadline_at', '/?sort=-deadline_at&filter=bar'],
            [['sort' => 'deadline_at', 'filter' => 'bar'], 'assigned_user', '/?sort=assigned_user&filter=bar'],
            [['filter' => 'bar', 'sort' => 'deadline_at'], 'deadline_at', '/?filter=bar&sort=-deadline_at'],
        ];
    }
}
