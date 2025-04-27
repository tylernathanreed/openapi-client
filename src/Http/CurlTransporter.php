<?php

namespace Reedware\OpenApi\Client\Http;

use CurlHandle;
use Reedware\OpenApi\Client\Configuration;

class CurlTransporter extends Transporter
{
    public function newResponse(Request $request, Configuration $config): Response
    {
        $handle = $this->newCurlHandle($request, $config);

        $response = curl_exec($handle) ?: null;

        assert(is_string($response) || is_null($response));

        return new Response(
            status: curl_getinfo($handle, CURLINFO_HTTP_CODE),
            body: $response,
        );
    }

    protected function newCurlHandle(Request $request, Configuration $config): CurlHandle
    {
        $handle = curl_init();

        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_VERBOSE, (bool) $config->debug);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_URL, $request->uri);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        // curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, $config->verify);
        // curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, $config->verify);

        return $handle;
    }
}
