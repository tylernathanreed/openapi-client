<?php

namespace Reedware\OpenApi\Client\Http;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\RequestOptions;
use Psr\Http\Client\ClientInterface;
use Reedware\OpenApi\Client\Configuration;

class GuzzleTransporter extends Transporter
{
    public function newResponse(Request $request, Configuration $config): Response
    {
        $response = $this->newClient($config)->sendRequest(new GuzzleRequest(
            $request->method,
            $request->uri,
            $request->headers,
            $request->body,
        ));

        return new Response(
            status: $response->getStatusCode(),
            body: $response->getBody(),
        );
    }

    protected function newClient(Configuration $config): ClientInterface
    {
        return new Guzzle([
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::CRYPTO_METHOD => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
            RequestOptions::DEBUG => $config->debug,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::TIMEOUT => 30,
        ]);
    }
}
