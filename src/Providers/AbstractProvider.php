<?php

namespace Dyndns\Providers;

use Dyndns\Config;
use Dyndns\HttpRequest;
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

    /** @var object */
    protected $httpRequest;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct($domain, array $config, Logger $logger, $httpRequest)
    {
        if (!is_object($httpRequest) || !method_exists($httpRequest, 'request')) {
            throw new RuntimeException('Invalid HTTP request handler');
        }

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
}
