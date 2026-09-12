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
        $sensitiveKeys = array('password', 'php_auth_pw', 'authorization', 'http_authorization');
        foreach ($params as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $params[$key] = '***';
                continue;
            }

            $params[$key] = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', is_scalar($value) ? (string) $value : json_encode($value));
        }

        $message = date('c') . ' - ' . $ipAddress . ' - ' . http_build_query($params, '', ' / ');
        if ($extra !== '') {
            $message .= ' - ' . $extra;
        }

        file_put_contents($this->filePath, $message . PHP_EOL, FILE_APPEND);
    }
}
