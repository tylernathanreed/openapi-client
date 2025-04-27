<?php

namespace Reedware\OpenApi\Client\Http;

class Response
{
    public function __construct(
        public readonly int $status,
        public readonly ?string $body = null,
    ) {
    }
}
