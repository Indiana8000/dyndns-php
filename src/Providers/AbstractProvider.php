<?php

namespace Dyndns\Providers;

use Dyndns\Config;
use Dyndns\HttpRequestInterface;
use Dyndns\Logger;
use RuntimeException;

abstract class AbstractProvider implements ProviderInterface
{
    /** @var string */
    protected $domain;

    /** @var array<string, mixed> */
    protected $config;

    /** @var Logger */
    protected $logger;

    /** @var HttpRequestInterface */
    protected $httpRequest;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct($domain, array $config, Logger $logger, HttpRequestInterface $httpRequest)
    {
        $this->domain = $domain;
        $this->config = $config;
        $this->logger = $logger;
        $this->httpRequest = $httpRequest;
    }

    protected function getRelativeHostname($hostname)
    {
        return Config::getRelativeHostname($hostname, $this->domain);
    }

    protected function requireConfigValue($key)
    {
        if (!isset($this->config[$key]) || $this->config[$key] === '') {
            throw new RuntimeException('Missing provider config value: ' . $key);
        }

        return $this->config[$key];
    }

    protected function encodeJson($value, $errorContext)
    {
        $encoded = json_encode($value);
        if ($encoded === false) {
            throw new RuntimeException('Failed to encode ' . $errorContext . ': ' . json_last_error_msg());
        }

        return $encoded;
    }
}
