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
        return new self($_REQUEST, $_SERVER);
    }

    /**
     * @return array<string, mixed>
     */
    public function all()
    {
        return $this->params;
    }

    public function get($name)
    {
        $value = $this->params[$name] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    public function isUpdateRequest()
    {
        return $this->get('username') !== ''
            && $this->get('hostname') !== ''
            && $this->get('ip') !== '';
    }

    public function hasValidIpv4()
    {
        return filter_var($this->get('ip'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function getUserIpAddress()
    {
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        }

        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $this->server['HTTP_X_FORWARDED_FOR'])[0]);
        }

        return $this->server['REMOTE_ADDR'] ?? 'unknown';
    }
}
