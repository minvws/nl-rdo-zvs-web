<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

class FormLayout extends Component
{
    public function render(): View
    {
        return view('layouts.form');
    }
}
