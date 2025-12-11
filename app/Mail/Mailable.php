<?php

declare(strict_types=1);

namespace App\Mail;

use App\Config\Config;
use Illuminate\Mail\Mailable as IlluminateMailable;
use Illuminate\Mail\Mailables\Envelope;

use function sprintf;

abstract class Mailable extends IlluminateMailable
{
    abstract public function getSubject(): string;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('[%s]: %s', Config::string('app.name'), $this->getSubject()),
        );
    }
}
