<?php

require_once __DIR__ . '/src/Autoloader.php';

use Dyndns\Config;
use Dyndns\HttpClient;
use Dyndns\Logger;
use Dyndns\Request;
use Dyndns\Providers\ProviderFactory;

$request = Request::fromGlobals();
$config = Config::load(__DIR__);
$logger = new Logger($config->getLogFile());

$logger->logRequest($request->all(), $request->getUserIpAddress());

if ($request->isUpdateRequest() && $request->hasValidIpv4()) {
    $hostname = Config::normalizeHostname($request->get('hostname'));
    $domainMatch = $config->resolveDomain($hostname);

    if ($domainMatch !== null) {
        $account = $config->getAccount($domainMatch['config'], $request->get('username'));

        if ($account !== null
            && $config->passwordMatches($account, $request->get('password'))
            && $config->hostnameAllowed($account, $hostname, $domainMatch['domain'])
        ) {
            try {
                $provider = ProviderFactory::create(
                    $domainMatch['config']['provider'] ?? '',
                    $domainMatch['domain'],
                    $domainMatch['config'],
                    $logger,
                    new HttpClient()
                );

                if ($provider->update($hostname, $request->get('ip'))) {
                    $logger->logRequest($request->all(), $request->getUserIpAddress(), 'SUCCESS');
                }
            } catch (\Throwable $exception) {
                $logger->logRequest($request->all(), $request->getUserIpAddress(), 'ERROR: ' . $exception->getMessage());
            }
        }
    }
}

print 'OK';
