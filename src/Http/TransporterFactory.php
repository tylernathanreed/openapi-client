<?php

namespace Reedware\OpenApi\Client\Http;

use Reedware\OpenApi\Client\Http\Contracts\Transporter;

class TransporterFactory
{
    public static function make(): Transporter
    {
        return class_exists('GuzzleHttp\Client')
            ? new GuzzleTransporter()
            : new CurlTransporter();
    }
}
