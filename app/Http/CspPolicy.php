<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\Environment;
use Illuminate\Foundation\Vite;
use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Scheme;

use function is_file;

class CspPolicy implements Preset
{
    public function __construct(
        private readonly Environment $environment,
        private readonly Vite $vite,
    ) {
    }

    public function configure(Policy $policy): void
    {
        $hosts = [Keyword::SELF];

        if ($this->environment->isDevelopmentOrTesting() && is_file($this->vite->hotFile())) {
            // Allow to connect to and fetch assets from the Vite development server
            $hosts[] = 'http://127.0.0.1:5173';
            $hosts[] = 'ws://127.0.0.1:5173';
        }

        $policy
            ->add(Directive::BASE, [Keyword::NONE])
            ->add(Directive::BLOCK_ALL_MIXED_CONTENT, [])
            ->add(Directive::CONNECT, [...$hosts, Scheme::DATA])
            ->add(Directive::DEFAULT, [...$hosts, Scheme::DATA])
            ->add(Directive::FONT, [...$hosts, Scheme::DATA])
            ->add(Directive::FORM_ACTION, $hosts)
            ->add(Directive::FRAME, $hosts)
            ->add(Directive::FRAME_ANCESTORS, $hosts)
            ->add(Directive::IMG, [...$hosts, Scheme::DATA, Scheme::BLOB])
            ->add(Directive::MEDIA, $hosts)
            ->add(Directive::OBJECT, [Keyword::NONE])
            ->add(Directive::SCRIPT, $hosts)
            ->addNonce(Directive::SCRIPT)
            ->add(Directive::STYLE, $hosts)
            ->addNonce(Directive::STYLE)
            ->add(Directive::WORKER, $hosts);

        if (!$this->environment->isDevelopmentOrTesting()) {
            $policy->add(Directive::UPGRADE_INSECURE_REQUESTS, []);
        }
    }
}
