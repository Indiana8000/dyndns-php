<?php

namespace Dyndns\Providers;

use Dyndns\HttpClient;
use Dyndns\Logger;
use InvalidArgumentException;

class ProviderFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create($providerName, $domain, array $config, Logger $logger, HttpClient $httpClient)
    {
        switch (strtolower((string) $providerName)) {
            case 'hetzner':
                return new HetznerProvider($domain, $config, $logger, $httpClient);

            case 'autodns':
            case 'internetx':
                return new AutoDnsProvider($domain, $config, $logger, $httpClient);

            default:
                throw new InvalidArgumentException('Unsupported provider: ' . $providerName);
        }
    }
}
