<?php

namespace Dyndns;

use RuntimeException;

class Config
{
    /** @var array<string, mixed> */
    private $config;

    /**
     * @param array<string, mixed> $config
     */
    private function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function load($basePath)
    {
        $configPath = $basePath . '/config.php';
        if (!is_file($configPath)) {
            $configPath = $basePath . '/config.dist.php';
        }

        if (!is_file($configPath)) {
            throw new RuntimeException('Missing config.php or config.dist.php');
        }

        $config = require $configPath;
        if (!is_array($config)) {
            throw new RuntimeException('Configuration file must return an array');
        }

        if (empty($config['log_file'])) {
            $config['log_file'] = $basePath . '/ddns.log';
        }

        return new self($config);
    }

    public function getLogFile()
    {
        return $this->config['log_file'];
    }

    /**
     * @return array{domain:string,config:array<string,mixed>}|null
     */
    public function resolveDomain($hostname)
    {
        $hostname = self::normalizeHostname($hostname);
        $bestMatch = null;

        foreach (($this->config['domains'] ?? array()) as $domain => $domainConfig) {
            if (!is_array($domainConfig)) {
                continue;
            }

            $normalizedDomain = self::normalizeHostname($domain);
            if ($normalizedDomain === '') {
                continue;
            }

            if ($hostname !== $normalizedDomain && substr($hostname, -strlen('.' . $normalizedDomain)) !== '.' . $normalizedDomain) {
                continue;
            }

            if ($bestMatch === null || strlen($normalizedDomain) > strlen($bestMatch['domain'])) {
                $bestMatch = array(
                    'domain' => $normalizedDomain,
                    'config' => $domainConfig,
                );
            }
        }

        return $bestMatch;
    }

    public static function normalizeHostname($hostname)
    {
        return strtolower(trim((string) $hostname, ". \t\n\r\0\x0B"));
    }

    public static function getRelativeHostname($hostname, $domain)
    {
        $hostname = self::normalizeHostname($hostname);
        $domain = self::normalizeHostname($domain);

        if ($hostname === $domain) {
            return '@';
        }

        $suffix = '.' . $domain;
        if (substr($hostname, -strlen($suffix)) !== $suffix) {
            return null;
        }

        return substr($hostname, 0, -strlen($suffix));
    }

    /**
     * @param array<string, mixed> $domainConfig
     * @return array<string, mixed>|null
     */
    public function getAccount(array $domainConfig, $username)
    {
        $accounts = $domainConfig['accounts'] ?? array();
        if (!is_array($accounts) || !array_key_exists($username, $accounts) || !is_array($accounts[$username])) {
            return null;
        }

        return $accounts[$username];
    }

    /**
     * @param array<string, mixed> $account
     */
    public function passwordMatches(array $account, $password)
    {
        return !empty($account['password_hash'])
            && is_string($account['password_hash'])
            && password_verify($password, $account['password_hash']);
    }

    /**
     * @param array<string, mixed> $account
     */
    public function hostnameAllowed(array $account, $hostname, $domain)
    {
        $allowedHostnames = $account['hostnames'] ?? array();
        if (!is_array($allowedHostnames)) {
            return false;
        }

        $hostname = self::normalizeHostname($hostname);
        $domain = self::normalizeHostname($domain);
        $relativeHostname = self::getRelativeHostname($hostname, $domain);
        if ($relativeHostname === null) {
            return false;
        }

        foreach ($allowedHostnames as $allowedHostname) {
            $allowedHostname = self::normalizeHostname($allowedHostname);
            if ($allowedHostname === '') {
                continue;
            }

            if ($allowedHostname === $hostname || $allowedHostname === '*') {
                return true;
            }

            if ($allowedHostname === '@' && $relativeHostname === '@') {
                return true;
            }

            if ($allowedHostname === $domain && $relativeHostname === '@') {
                return true;
            }

            if ($allowedHostname === $relativeHostname) {
                return true;
            }

            if ($allowedHostname . '.' . $domain === $hostname) {
                return true;
            }
        }

        return false;
    }
}
