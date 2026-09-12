<?php

namespace Dyndns\Providers;

use Dyndns\HttpRequestInterface;
use Dyndns\Logger;
use InvalidArgumentException;

class ProviderFactory
{
    /** @var array<string, class-string<ProviderInterface>> */
    private const PROVIDERS = array(
        'hetzner' => Hetzner::class,
        'internetx' => InternetX::class,
        'schlundtech' => SchlundTech::class,
    );

    /**
     * @param array<string, mixed> $config
     */
    public static function create($providerName, $domain, array $config, Logger $logger, HttpRequestInterface $httpRequest)
    {
        if (
            is_string($providerName)
            && class_exists($providerName)
            && is_subclass_of($providerName, ProviderInterface::class)
        ) {
            return new $providerName($domain, $config, $logger, $httpRequest);
        }

        $providerClass = self::PROVIDERS[strtolower((string) $providerName)] ?? null;
        if ($providerClass === null) {
            throw new InvalidArgumentException('Unsupported provider: ' . $providerName);
        }

        return new $providerClass($domain, $config, $logger, $httpRequest);
    }
}
