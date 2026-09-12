<?php

namespace Dyndns\Providers;

use Dyndns\HttpRequestInterface;
use Dyndns\Logger;
use InvalidArgumentException;

class ProviderFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create($providerName, $domain, array $config, Logger $logger, $httpRequest)
    {
        if (!$httpRequest instanceof HttpRequestInterface) {
            throw new InvalidArgumentException(
                'Invalid HTTP request handler; expected ' . HttpRequestInterface::class . ', got ' . get_debug_type($httpRequest)
            );
        }

        if (
            is_string($providerName)
            && class_exists($providerName)
            && is_subclass_of($providerName, ProviderInterface::class)
        ) {
            return new $providerName($domain, $config, $logger, $httpRequest);
        }

        switch (strtolower((string) $providerName)) {
            case 'hetzner':
                return new Hetzner($domain, $config, $logger, $httpRequest);

            case 'autodns':
                return new AutoDnsProvider($domain, $config, $logger, $httpRequest);

            case 'internetx':
                return new InternetX($domain, $config, $logger, $httpRequest);

            case 'schlundtech':
                return new SchlundTech($domain, $config, $logger, $httpRequest);

            default:
                throw new InvalidArgumentException('Unsupported provider: ' . $providerName);
        }
    }
}
