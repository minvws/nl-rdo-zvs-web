<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmShowRequest;
use Illuminate\Contracts\View\View;
use Illuminate\View\Factory;

final readonly class ConfirmController
{
    public function __construct(
        private Factory $view,
    ) {
    }

    public function __invoke(ConfirmShowRequest $request): View
    {
        return $this->view->make('confirm.show', [
            'message' => $request->input('message'),
            'confirmUrl' => $request->input('confirm_url'),
            'cancelUrl' => $request->input('cancel_url'),
            'method' => $request->input('method'),
        ]);
    }
}
