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
    public static function create($providerName, $domain, array $config, Logger $logger, HttpRequestInterface $httpRequest)
    {
        if (
            is_string($providerName)
            && class_exists($providerName)
            && (
                is_subclass_of($providerName, ProviderInterface::class)
                || is_subclass_of($providerName, AbstractProvider::class)
            )
        ) {
            return new $providerName($domain, $config, $logger, $httpRequest);
        }

        switch (strtolower((string) $providerName)) {
            case 'hetzner':
                return new HetznerProvider($domain, $config, $logger, $httpRequest);

            case 'autodns':
                if (isset($config['context']) && $config['context'] !== '') {
                    return new AutoDnsProvider($domain, $config, $logger, $httpRequest);
                }

                return new InternetX($domain, $config, $logger, $httpRequest);

            case 'internetx':
                return new InternetX($domain, $config, $logger, $httpRequest);

            case 'schlundtech':
                return new SchlundTech($domain, $config, $logger, $httpRequest);

            default:
                throw new InvalidArgumentException('Unsupported provider: ' . $providerName);
        }
    }
}
