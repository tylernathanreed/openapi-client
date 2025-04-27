<?php

namespace Reedware\OpenApi\Client\Http;

use Reedware\OpenApi\Client\Http\Exceptions\InvalidBodyHttpException;
use Reedware\OpenApi\Client\Http\Exceptions\MethodNotAllowedHttpException;
use Reedware\OpenApi\Client\Http\Exceptions\NotFoundHttpException;
use Reedware\OpenApi\Client\Http\Exceptions\UnsupportedStatusCodeHttpException;

class Processor
{
    public function __construct(
        protected Deserializer $deserializer
    ) {
    }

    /**
     * @param array{0:class-string<Dto>}|class-string<Dto>|true $schema
     *
     * @return ($schema is true ? true : ($schema is array ? list<Dto> : Dto))
     */
    public function process(
        PendingOperation $operation,
        Response $response,
        int $successCode,
        array|string|bool $schema
    ): array|Dto|bool {
        $status = $response->status;

        if ($status === 404) {
            throw new NotFoundHttpException($response, $operation->getExpandedUri());
        }

        if ($status === 405) {
            throw new MethodNotAllowedHttpException($response, sprintf(
                '%s@%s',
                $operation->getExpandedUri(),
                strtoupper($operation->method),
            ));
        }

        if ($status != $successCode) {
            throw new UnsupportedStatusCodeHttpException($response, sprintf(
                'Unsupported Status Code (Expected: %s).',
                $successCode,
            ));
        }

        if ($schema === true) {
            return true;
        }

        $body = (string) $response->body;

        $data = json_decode($body, true);

        if (! is_array($data)) {
            throw new InvalidBodyHttpException($response, 'Unable to decode response body: ' . $body);
        }

        if (is_array($schema)) {
            /** @var list<array<string,mixed>> $data */
            return $this->deserializer->deserialize($data, $schema[0], array: true);
        } else {
            /** @var array<string,mixed> $data */
            return $this->deserializer->deserialize($data, $schema);
        }
    }
}
