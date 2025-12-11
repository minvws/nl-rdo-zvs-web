<?php

declare(strict_types=1);

namespace App\View;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\Factory;

use function array_merge;
use function is_string;
use function sprintf;

readonly class HtmxHelper
{
    public const string HTMX_REQUEST_HEADER = 'HX-Request';

    public function __construct(
        private Factory $view,
    ) {
    }

    /**
     * @param array<string, mixed> $viewData
     */
    public function makeFormViewResponse(Request $request, string $viewName, array $viewData = []): Response
    {
        if ($this->isHtmxRequest($request)) {
            $hxTarget = $request->input('hx-target');
            $headers = is_string($hxTarget) ? ['HX-Retarget' => sprintf('#%s', $hxTarget)] : [];

            return new Response($this->view->make($viewName, $viewData)->render(), headers: $headers);
        }

        return new Response($this->view->make('form', array_merge(['view' => $viewName], $viewData)));
    }

    public function isHtmxRequest(Request $request): bool
    {
        return $request->hasHeader(self::HTMX_REQUEST_HEADER);
    }
}
