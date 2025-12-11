<?php

declare(strict_types=1);

use App\Models\ApiUser;
use App\Models\ContactPetition;
use App\Models\DecisionPetition;
use App\Models\DepartmentUser;
use App\Models\EloquentModel;
use App\Models\User;

arch('app')
    ->expect('App\Models')
    ->toExtend(EloquentModel::class)
    ->ignoring([
        'App\Models\Builder',
        'App\Models\Concerns',
        'App\Models\Casts',
        'App\Models\Contracts',
        'App\Models\Observers',
        'App\Models\Scopes',
        User::class,
        ApiUser::class,
        DepartmentUser::class,
        DecisionPetition::class,
        ContactPetition::class,
    ]);
