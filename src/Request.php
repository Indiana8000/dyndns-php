<?php

namespace Dyndns;

class Request
{
    /** @var array<string, mixed> */
    private $params;

    /** @var array<string, mixed> */
    private $server;

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $server
     */
    public function __construct(array $params, array $server)
    {
        $this->params = $params;
        $this->server = $server;
    }

    public static function fromGlobals()
    {
        $params = array_merge($_GET, $_POST);

        if (empty($params['username']) && !empty($_SERVER['PHP_AUTH_USER'])) {
            $params['username'] = $_SERVER['PHP_AUTH_USER'];
        }

        if (empty($params['password']) && !empty($_SERVER['PHP_AUTH_PW'])) {
            $params['password'] = $_SERVER['PHP_AUTH_PW'];
        }

        return new self($params, $_SERVER);
    }

    /**
     * @return array<string, mixed>
     */
    public function all()
    {
        return $this->params;
    }

    /**
     * @return array<string, string>
     */
    public function getLogContext()
    {
        return array(
            'username' => $this->get('username'),
            'hostname' => $this->get('hostname'),
            'ip' => $this->get('ip'),
        );
    }

    public function get($name)
    {
        $value = $this->params[$name] ?? '';
        if ((!is_string($value) || trim($value) === '') && $name === 'username' && !empty($this->server['PHP_AUTH_USER'])) {
            return trim((string) $this->server['PHP_AUTH_USER']);
        }

        if ((!is_string($value) || trim($value) === '') && $name === 'password' && !empty($this->server['PHP_AUTH_PW'])) {
            return trim((string) $this->server['PHP_AUTH_PW']);
        }

        return is_string($value) ? trim($value) : '';
    }

    public function isUpdateRequest()
    {
        return $this->get('hostname') !== ''
            && $this->get('ip') !== ''
            && $this->get('password') !== '';
    }

    public function hasValidIpv4()
    {
        return filter_var($this->get('ip'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function getUserIpAddress()
    {
        return $this->server['REMOTE_ADDR'] ?? 'unknown';
    }
}
