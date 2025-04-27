<?php

namespace Reedware\OpenApi\Client\Http;

class Request
{
    public function __construct(
        /** @var 'get'|'post'|'put'|'patch'|'delete' */
        public readonly string $method,

        /** @var non-empty-string */
        public readonly string $uri,

        /** @var array<string,array<string>|string> */
        public readonly array $headers = [],

        /** @var non-empty-string|null */
        public readonly ?string $body = null,
    ) {
    }
}
