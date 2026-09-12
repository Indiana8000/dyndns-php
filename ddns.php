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

$logger->logRequest($request->getLogContext(), $request->getUserIpAddress());

if (!$request->isUpdateRequest()) {
    $logger->logRequest($request->getLogContext(), $request->getUserIpAddress(), 'ERROR: missing required parameters');
    http_response_code(400);
    print 'FAIL';
    return;
}

if (!$request->hasValidIpv4()) {
    $logger->logRequest($request->getLogContext(), $request->getUserIpAddress(), 'ERROR: invalid IPv4 address');
    http_response_code(400);
    print 'FAIL';
    return;
}

$hostname = Config::normalizeHostname($request->get('hostname'));
$domainMatch = $config->resolveDomain($hostname);
if ($domainMatch === null) {
    $logger->logRequest($request->getLogContext(), $request->getUserIpAddress(), 'ERROR: unknown hostname');
    http_response_code(404);
    print 'FAIL';
    return;
}

$account = $config->getAccount($domainMatch['config'], $request->get('username'));
if ($account === null || !$config->passwordMatches($account, $request->get('password'))) {
    $logger->logRequest($request->getLogContext(), $request->getUserIpAddress(), 'ERROR: authentication failed');
    http_response_code(401);
    print 'FAIL';
    return;
}

if (!$config->hostnameAllowed($account, $hostname, $domainMatch['domain'])) {
    $logger->logRequest($request->getLogContext(), $request->getUserIpAddress(), 'ERROR: hostname not allowed');
    http_response_code(403);
    print 'FAIL';
    return;
}

try {
    $provider = ProviderFactory::create(
        $domainMatch['config']['provider'] ?? '',
        $domainMatch['domain'],
        $domainMatch['config'],
        $logger,
        new HttpClient()
    );

    if (!$provider->update($hostname, $request->getIpAddressForUpdate())) {
        $logger->logRequest($request->getLogContext(), $request->getUserIpAddress(), 'ERROR: provider update failed');
        http_response_code(502);
        print 'FAIL';
        return;
    }
} catch (\Throwable $exception) {
    $logger->logRequest($request->getLogContext(), $request->getUserIpAddress(), 'ERROR: ' . $exception->getMessage());
    http_response_code(500);
    print 'FAIL';
    return;
}

$logger->logRequest($request->getLogContext(), $request->getUserIpAddress(), 'SUCCESS');
print 'OK';
