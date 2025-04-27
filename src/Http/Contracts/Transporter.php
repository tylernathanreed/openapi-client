<?php

namespace Reedware\OpenApi\Client\Http\Contracts;

use Reedware\OpenApi\Client\Configuration;
use Reedware\OpenApi\Client\Http\PendingOperation;
use Reedware\OpenApi\Client\Http\Request;
use Reedware\OpenApi\Client\Http\Response;

interface Transporter
{
    public function newRequest(PendingOperation $operation, Configuration $config): Request;

    public function newResponse(Request $request, Configuration $config): Response;
}
