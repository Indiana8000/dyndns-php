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
        if (isset($params['password'])) {
            $params['password'] = '***';
        }

        $message = date('c') . ' - ' . $ipAddress . ' - ' . http_build_query($params, '', ' / ');
        if ($extra !== '') {
            $message .= ' - ' . $extra;
        }

        file_put_contents($this->filePath, $message . PHP_EOL, FILE_APPEND);
    }
}
