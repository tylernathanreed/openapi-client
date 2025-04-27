<?php

namespace Reedware\OpenApi\Client\Http;

use InvalidArgumentException;
use Reedware\OpenApi\Client\Configuration;
use Reedware\OpenApi\Client\Http\Contracts\Transporter as TransporterContract;

/**
 * @phpstan-type THeaders array{
 *    Accept:string,
 *    Authorization?:string,
 *    'User-Agent'?:string,
 * }
 */
abstract class Transporter implements TransporterContract
{
    public function newRequest(PendingOperation $operation, Configuration $config): Request
    {
        return new Request(
            method: $operation->method,
            uri: $this->getUri($operation, $config),
            headers: $this->getHeaders($operation, $config),
            body: $this->getBody($operation, $config),
        );
    }

    /** @return non-empty-string */
    protected function getUri(PendingOperation $operation, Configuration $config): string
    {
        $host = trim($config->host, '/');
        $path = ltrim($operation->getExpandedUri(), '/');

        $query = ! empty($operation->query)
            ? http_build_query($operation->query, '', '&', PHP_QUERY_RFC3986)
            : null;

        return ! is_null($query)
            ? "{$host}/{$path}?{$query}"
            : "{$host}/{$path}";
    }

    /** @return THeaders */
    protected function getHeaders(PendingOperation $operation, Configuration $config): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Host' => $config->host,
        ];

        if (! empty($config->accessToken)) {
            $headers['Authorization'] = trim('Bearer ' . $config->accessToken);
        } elseif (! empty($config->username) && ! empty($config->password)) {
            $headers['Authorization'] = 'Basic ' . base64_encode("{$config->username}:{$config->password}");
        }

        if (! empty($config->userAgent)) {
            $headers['User-Agent'] = trim($config->userAgent);
        }

        return $headers;
    }

    /** @return non-empty-string|null */
    protected function getBody(PendingOperation $operation, Configuration $config): ?string
    {
        if (empty($operation->body)) {
            return null;
        }

        $json = json_encode($operation->body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Unable to encode json: ' . json_last_error_msg());
        }

        assert(is_string($json) && ! empty($json));

        return $json;
    }
}
