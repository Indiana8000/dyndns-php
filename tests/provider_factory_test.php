<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Autoloader.php';

use Dyndns\HttpRequest;
use Dyndns\HttpClient;
use Dyndns\Logger;
use Dyndns\Providers\AutoDnsProvider;
use Dyndns\Providers\Hetzner;
use Dyndns\Providers\HetznerProvider;
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

$schlundTech = ProviderFactory::create('schlundtech', 'example.com', $config, $logger, $httpRequest);
assertInstanceOf(SchlundTech::class, $schlundTech, 'schlundtech should resolve to the SchlundTech provider.');

$hetzner = ProviderFactory::create('hetzner', 'example.com', $config, $logger, $httpRequest);
assertInstanceOf(Hetzner::class, $hetzner, 'hetzner should resolve to the Hetzner provider.');

$legacyAutoDns = ProviderFactory::create('autodns', 'example.com', $config, $logger, $httpRequest);
assertInstanceOf(AutoDnsProvider::class, $legacyAutoDns, 'autodns should remain a compatibility alias through the legacy AutoDnsProvider wrapper.');

$legacyAutoDnsWithContext = ProviderFactory::create('autodns', 'example.com', array('api_token' => 'token', 'context' => 10), $logger, $httpRequest);
assertInstanceOf(AutoDnsProvider::class, $legacyAutoDnsWithContext, 'autodns should continue to resolve to the legacy AutoDnsProvider wrapper.');

$legacyAutoDnsWithInternetXContext = ProviderFactory::create('autodns', 'example.com', array('api_token' => 'token', 'context' => 4), $logger, $httpRequest);
assertInstanceOf(AutoDnsProvider::class, $legacyAutoDnsWithInternetXContext, 'Legacy autodns configs should still accept the historical InternetX context override.');

$legacyHttpClient = ProviderFactory::create('internetx', 'example.com', $config, $logger, new HttpClient());
assertInstanceOf(InternetX::class, $legacyHttpClient, 'Legacy HttpClient instances should remain accepted.');

$legacyAutoDnsClass = ProviderFactory::create(AutoDnsProvider::class, 'example.com', $config, $logger, $httpRequest);
assertInstanceOf(AutoDnsProvider::class, $legacyAutoDnsClass, 'Legacy AutoDnsProvider class names should remain instantiable.');

$legacyHetznerClass = ProviderFactory::create(HetznerProvider::class, 'example.com', $config, $logger, $httpRequest);
assertInstanceOf(HetznerProvider::class, $legacyHetznerClass, 'Legacy HetznerProvider class names should remain instantiable.');

$legacyAutoDnsDirect = new AutoDnsProvider('example.com', $config, $logger, new HttpClient());
assertInstanceOf(AutoDnsProvider::class, $legacyAutoDnsDirect, 'Legacy AutoDnsProvider direct construction should remain supported.');

$legacyHetznerDirect = new HetznerProvider('example.com', $config, $logger, new HttpClient());
assertInstanceOf(HetznerProvider::class, $legacyHetznerDirect, 'Legacy HetznerProvider direct construction should remain supported.');

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

$legacyAutoDnsContextMethod = new ReflectionMethod(AutoDnsProvider::class, 'getContextId');
$legacyAutoDnsContextMethod->setAccessible(true);
assertSame(10, $legacyAutoDnsContextMethod->invoke($legacyAutoDns), 'Legacy autodns configs should default to the legacy AutoDNS context.');
assertSame(10, $legacyAutoDnsContextMethod->invoke($legacyAutoDnsWithContext), 'Legacy autodns configs should continue honoring explicit context values.');
assertSame(4, $legacyAutoDnsContextMethod->invoke($legacyAutoDnsWithInternetXContext), 'Legacy autodns configs should allow the historical InternetX context override.');

assertThrows(
    static function () use ($config, $logger, $httpRequest): void {
        $provider = ProviderFactory::create('autodns', 'example.com', array('api_token' => 'token', 'context' => 99), $logger, $httpRequest);
        $contextMethod = new ReflectionMethod(AutoDnsProvider::class, 'getContextId');
        $contextMethod->setAccessible(true);
        $contextMethod->invoke($provider);
    },
    RuntimeException::class,
    'Unsupported legacy autodns context: 99',
    'Legacy autodns should reject unsupported context overrides.'
);

$schlundTechContextMethod = new ReflectionMethod(SchlundTech::class, 'getContextId');
$schlundTechContextMethod->setAccessible(true);
assertSame(10, $schlundTechContextMethod->invoke($schlundTech), 'SchlundTech should use context 10 internally.');

echo "All provider factory tests passed.\n";
