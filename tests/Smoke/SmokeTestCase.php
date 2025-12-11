<?php

declare(strict_types=1);

namespace Tests\Smoke;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\BrowserKitTesting\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Helpers\Models\UserHelper;

class SmokeTestCase extends TestCase
{
    use DatabaseTransactions;
    use UserHelper;
    use WithFaker;

    public string $baseUrl = 'http://localhost';

    /**
     * The original method in BrowserKitTesting\TestCase fails on redirects
     *
     * @param string $method
     * @param string $uri
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     *
     * @return $this
     *
     * @throws BindingResolutionException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    protected function makeRequest($method, $uri, $parameters = [], $cookies = [], $files = []): self
    {
        $uri = $this->prepareUrlForRequest($uri);

        $this->call($method, $uri, $parameters, $cookies, $files);

        // this is the only change: assertPageLoaded() is removed
        $this->clearInputs()->followRedirects();

        $this->currentUri = $this->app->make('request')->fullUrl();

        $this->crawler = new Crawler($this->response->getContent(), $this->currentUri);

        return $this;
    }
}
