<?php

namespace Reedware\OpenApi\Client\Http;

class PendingOperation
{
    public function __construct(
        public string $uri,

        /** @var 'get'|'post'|'put'|'patch'|'delete' */
        public string $method,

        /** @var Dto|array<string,mixed> */
        public Dto|array $body = [],

        /** @var array<string,mixed> */
        public array $header = [],

        /** @var array<string,mixed> */
        public array $query = [],

        /** @var array<string,int|string> */
        public array $path = [],
    ) {}

    public function getExpandedUri(): string
    {
        return strtr($this->uri, array_combine(
            keys: array_map(fn($v) => "{{$v}}", array_keys($this->path)),
            values: array_values($this->path),
        ));
    }
}
