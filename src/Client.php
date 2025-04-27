<?php

namespace Reedware\OpenApi\Client;

use Closure;
use Reedware\OpenApi\Client\Http\Contracts\Transporter as TransporterContract;
use Reedware\OpenApi\Client\Http\Deserializer;
use Reedware\OpenApi\Client\Http\Dto;
use Reedware\OpenApi\Client\Http\Exceptions\StrayRequestException;
use Reedware\OpenApi\Client\Http\PendingOperation;
use Reedware\OpenApi\Client\Http\PolymorphicDto;
use Reedware\OpenApi\Client\Http\Processor;
use Reedware\OpenApi\Client\Http\Request;
use Reedware\OpenApi\Client\Http\Response;
use Reedware\OpenApi\Client\Http\TransporterFactory;

class Client
{
    use PerformsOperations;

    public readonly Configuration $configuration;
    public readonly TransporterContract $transporter;
    public readonly Processor $processor;

    /** @var list<Closure(Request):?Response> */
    protected array $stubCallbacks = [];

    public function __construct(
        Configuration $configuration,
        ?TransporterContract $transporter = null,
        ?Processor $processor = null
    ) {
        $this->configuration = $configuration;
        $this->transporter = $transporter ?: TransporterFactory::make();
        $this->processor = $processor ?: new Processor(new Deserializer());
    }

    /**
     * @phpstan-template TDto of Dto
     * 
     * @param 'get'|'post'|'put'|'patch'|'delete' $method
     * @param array{0:class-string<TDto>}|class-string<TDto>|true $schema
     * @param Dto|array<string,mixed> $body
     * @param array<string,mixed> $header
     * @param array<string,mixed> $query
     * @param array<string,int|string> $path
     *
     * @return (
     *     $schema is array ? (TDto is PolymorphicDto ? list<Dto> : list<TDto>) : (
     *     $schema is string ? (TDto is PolymorphicDto ? Dto : TDto) : (
     *     true
     * )))
     */
    public function call(
        string $uri,
        string $method,
        int $success,
        array|string|bool $schema,
        Dto|array $body = [],
        array $header = [],
        array $query = [],
        array $path = [],
    ): array|Dto|bool {
        $operation = new PendingOperation(
            uri: $uri,
            method: $method,
            body: $body,
            header: $header,
            query: $query,
            path: $path,
        );

        $request = $this->transporter->newRequest($operation, $this->configuration);

        $response = $this->handleStubCallbacks($request)
            ?: $this->transporter->newResponse($request, $this->configuration);

        return $this->processor->process($operation, $response, $success, $schema);
    }

    /** @param Closure(Request):?Response $callback */
    public function fake(Closure $callback): static
    {
        $this->stubCallbacks[] = $callback;

        return $this;
    }

    protected function handleStubCallbacks(Request $request): ?Response
    {
        foreach ($this->stubCallbacks as $callback) {
            if (! is_null($response = $callback($request))) {
                return $response;
            }
        }

        if (! empty($this->stubCallbacks)) {
            throw new StrayRequestException(sprintf(
                'Attempted request to [%s] without a matching fake.',
                $request->uri,
            ));
        }

        return null;
    }
}
