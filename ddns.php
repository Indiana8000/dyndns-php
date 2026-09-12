<?php

require_once __DIR__ . '/src/Autoloader.php';

use Dyndns\Config;
use Dyndns\HttpRequest;
use Dyndns\Logger;
use Dyndns\Request;
use Dyndns\Providers\ProviderFactory;

$request = Request::fromGlobals();
$config = Config::load(__DIR__);
$logger = new Logger($config->getLogFile());
$logContext = $request->getLogContext();
$resolvedIp = $request->getIpAddressForUpdate();
if ($resolvedIp === '' && $config->allowsRemoteAddrFallback()) {
    $resolvedIp = trim((string) $request->getUserIpAddress());
}

$logContext['ip'] = $resolvedIp;
$statusCode = 200;
$responseBody = 'OK';
$logMessage = 'SUCCESS';

if (!$request->isUpdateRequest()) {
    $statusCode = 400;
    $responseBody = 'FAIL';
    $logMessage = 'ERROR: missing required parameters';
} elseif (filter_var($resolvedIp, FILTER_VALIDATE_IP) === false) {
    $statusCode = 400;
    $responseBody = 'FAIL';
    $logMessage = 'ERROR: invalid IP address';
} else {
    $hostname = Config::normalizeHostname($request->get('hostname'));
    $domainMatch = $config->resolveDomain($hostname);
    if ($domainMatch === null) {
        $statusCode = 404;
        $responseBody = 'FAIL';
        $logMessage = 'ERROR: unknown hostname';
    } else {
        $account = $config->getAccount($domainMatch['config'], $request->get('username'));
        if ($account === null || !$config->passwordMatches($account, $request->get('password'))) {
            $statusCode = 401;
            $responseBody = 'FAIL';
            $logMessage = 'ERROR: authentication failed';
        } elseif (!$config->hostnameAllowed($account, $hostname, $domainMatch['domain'])) {
            $statusCode = 403;
            $responseBody = 'FAIL';
            $logMessage = 'ERROR: hostname not allowed';
        } else {
            try {
                $provider = ProviderFactory::create(
                    $domainMatch['config']['provider'] ?? '',
                    $domainMatch['domain'],
                    $domainMatch['config'],
                    $logger,
                    new HttpRequest()
                );

                if (!$provider->update($hostname, $resolvedIp)) {
                    $statusCode = 502;
                    $responseBody = 'FAIL';
                    $logMessage = 'ERROR: provider update failed';
                }
            } catch (\Throwable $exception) {
                $statusCode = 500;
                $responseBody = 'FAIL';
                $logMessage = 'ERROR: ' . $exception->getMessage();
            }
        }
    }
}

http_response_code($statusCode);
if ($statusCode === 401) {
    header('WWW-Authenticate: Basic realm="dyndns-php"');
}

$logger->logRequest($logContext, $request->getUserIpAddress(), $logMessage);
print $responseBody;
