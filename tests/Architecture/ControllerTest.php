<?php

declare(strict_types=1);

arch('Controllers are final and do not extend anything')
    ->expect('App\Http\Controllers')
    ->toExtendNothing()
    ->toBeReadonly()
    ->toBeFinal();
