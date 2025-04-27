<?php

namespace Reedware\OpenApi\Client;

use Reedware\OpenApi\Client\Http\Exceptions\InvalidConfigurationException;

class Configuration
{
    public function __construct(
        public string $host = 'https://your-domain.atlassian.net',

        /** The access token for OAuth/Bearer authentication. */
        public ?string $accessToken = null,

        /** The username for HTTP basic authentication. */
        public ?string $username = null,

        /** The password for HTTP basic authentication. */
        public ?string $password = null,

        /** The user agent of the HTTP request. */
        public ?string $userAgent = null,

        /**
         * The debug switch or the debug file location.
         *
         * @var bool|resource
         *
         * @link https://docs.guzzlephp.org/en/stable/request-options.html#debug
         */
        public mixed $debug = false,
    ) {
        $this->validate();
    }

    protected function validate(): void
    {
        if (! empty($this->accessToken)) {
            return;
        }

        if (! empty($this->username) && ! empty($this->password)) {
            return;
        }

        throw new InvalidConfigurationException(sprintf(
            'Token Auth ([accessToken]) or Basic Auth ([username] and [password]) must be provided.'
        ));
    }
}
