<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Autoloader.php';

use Dyndns\HttpRequest;
use Dyndns\HttpClient;
use Dyndns\Logger;
use Dyndns\Providers\Hetzner;
use Dyndns\Providers\InternetX;
use Dyndns\Providers\ProviderFactory;
use Dyndns\Providers\SchlundTech;

function assertSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function assertInstanceOf($expectedClass, $actual, $message)
{
    if (!($actual instanceof $expectedClass)) {
        throw new RuntimeException($message . ' Expected instance of ' . $expectedClass . ', got ' . get_debug_type($actual));
    }
}

function assertThrows(callable $callback, $expectedException, $expectedMessage, $message)
{
    try {
        $callback();
    } catch (Throwable $throwable) {
        assertInstanceOf($expectedException, $throwable, $message . ' Wrong exception type.');
        assertSame($expectedMessage, $throwable->getMessage(), $message . ' Wrong exception message.');
        return;
    }

    throw new RuntimeException($message . ' Expected exception was not thrown.');
}

$logger = new Logger(dirname(__DIR__) . '/ddns.log');
$httpRequest = new HttpRequest();
$config = array('api_token' => 'token');

$internetX = ProviderFactory::create('internetx', 'example.com', $config, $logger, $httpRequest);
assertInstanceOf(InternetX::class, $internetX, 'internetx should resolve to the InternetX provider.');

$internetXMixedCase = ProviderFactory::create('InternetX', 'example.com', $config, $logger, $httpRequest);
assertInstanceOf(InternetX::class, $internetXMixedCase, 'Provider names should be matched case-insensitively for InternetX.');

$schlundTech = ProviderFactory::create('schlundtech', 'example.com', $config, $logger, $httpRequest);
assertInstanceOf(SchlundTech::class, $schlundTech, 'schlundtech should resolve to the SchlundTech provider.');

$schlundTechUpperCase = ProviderFactory::create('SCHLUNDTECH', 'example.com', $config, $logger, $httpRequest);
assertInstanceOf(SchlundTech::class, $schlundTechUpperCase, 'Provider names should be matched case-insensitively for SchlundTech.');

$hetzner = ProviderFactory::create('hetzner', 'example.com', $config, $logger, $httpRequest);
assertInstanceOf(Hetzner::class, $hetzner, 'hetzner should resolve to the Hetzner provider.');

$legacyHttpClient = ProviderFactory::create('internetx', 'example.com', $config, $logger, new HttpClient());
assertInstanceOf(InternetX::class, $legacyHttpClient, 'Legacy HttpClient instances should remain accepted.');

assertThrows(
    static function () use ($config, $logger, $httpRequest): void {
        ProviderFactory::create('unsupported-provider', 'example.com', $config, $logger, $httpRequest);
    },
    InvalidArgumentException::class,
    'Unsupported provider: unsupported-provider',
    'Unsupported providers should still be rejected.'
);

$internetXContextMethod = new ReflectionMethod(InternetX::class, 'getContextId');
$internetXContextMethod->setAccessible(true);
assertSame(4, $internetXContextMethod->invoke($internetX), 'InternetX should use context 4 internally.');

$schlundTechContextMethod = new ReflectionMethod(SchlundTech::class, 'getContextId');
$schlundTechContextMethod->setAccessible(true);
assertSame(10, $schlundTechContextMethod->invoke($schlundTech), 'SchlundTech should use context 10 internally.');

$removedAlias = 'auto' . 'dns';
assertThrows(
    static function () use ($config, $logger, $httpRequest, $removedAlias): void {
        ProviderFactory::create($removedAlias, 'example.com', $config, $logger, $httpRequest);
    },
    InvalidArgumentException::class,
    'Unsupported provider: ' . $removedAlias,
    'Removed legacy aliases should be rejected.'
);

echo "All provider factory tests passed.\n";
