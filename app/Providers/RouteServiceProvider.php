<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Decision;
use App\Models\Department;
use App\Models\EloquentModel;
use App\Models\Petition;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webmozart\Assert\Assert;

class RouteServiceProvider extends ServiceProvider
{
    private const string UUID_PATTERN = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    public function boot(): void
    {
        Route::pattern('petitionCategory', self::UUID_PATTERN);
        Route::pattern('petitionType', self::UUID_PATTERN);
        Route::pattern('contact', self::UUID_PATTERN);
        Route::pattern('decision', self::UUID_PATTERN);
        Route::pattern('petition', self::UUID_PATTERN);
        Route::pattern('relatedPetition', self::UUID_PATTERN);
        Route::pattern('publicHoliday', self::UUID_PATTERN);
        Route::pattern('petitionExport', self::UUID_PATTERN);
        Route::pattern('processingStep', self::UUID_PATTERN);
        Route::pattern('user', self::UUID_PATTERN);
        Route::pattern('timelineableId', self::UUID_PATTERN);
        Route::pattern('timelineable', self::UUID_PATTERN);

        Route::bind('relatedPetition', static function (string $value): Petition {
            return Petition::query()->findSole($value);
        });

        Route::bind('relatedDecision', static function (string $value): Decision {
            return Decision::query()->findSole($value);
        });

        Route::bind('department', static function (string $value): Department {
            return Department::query()->where('slug', $value)->sole();
        });

        Route::bind('timelineable', static function (string $value, RoutingRoute $route) {
            $timelineableType = $route->parameter('timelineableType');
            Assert::string($timelineableType);
            $model = Relation::getMorphedModel($timelineableType);
            Assert::subclassOf($model, EloquentModel::class);

            if ($route->hasParameter('department')) {
                $department = $route->parameter('department');
                Assert::isInstanceOf($department, Department::class);

                return $model::query()
                    ->where('department_id', $department->id)
                    ->where('id', $value)
                    ->firstOrFail();
            }

            // @codeCoverageIgnoreStart
            return $model::query()->findSole($value);
            // @codeCoverageIgnoreEnd
        });
    }
}
