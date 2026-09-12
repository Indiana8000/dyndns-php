<?php

namespace Dyndns;

class Logger
{
    /** @var string */
    private $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function logRequest(array $params, $ipAddress, $extra = '')
    {
        $params = $this->sanitizeParams($params);

        $message = date('c') . ' - ' . $ipAddress . ' - ' . http_build_query($params, '', ' / ');
        if ($extra !== '') {
            $message .= ' - ' . $extra;
        }

        file_put_contents($this->filePath, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function sanitizeParams(array $params)
    {
        $sanitized = array();
        foreach ($params as $key => $value) {
            $sanitized[$key] = $this->sanitizeValue((string) $key, $value);
        }

        return $sanitized;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function sanitizeValue($key, $value)
    {
        $sensitiveKeys = array('password', 'php_auth_pw', 'authorization', 'http_authorization');
        if (in_array(strtolower($key), $sensitiveKeys, true)) {
            return '***';
        }

        if (is_array($value)) {
            $sanitized = array();
            foreach ($value as $childKey => $childValue) {
                $sanitized[$childKey] = $this->sanitizeValue((string) $childKey, $childValue);
            }

            return $sanitized;
        }

        return preg_replace('/[\x00-\x1F\x7F]+/u', ' ', is_scalar($value) ? (string) $value : json_encode($value));
    }
}
