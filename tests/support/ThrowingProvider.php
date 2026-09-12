<?php

namespace Tests\Support;

require_once dirname(__DIR__, 2) . '/src/Autoloader.php';

use Dyndns\Providers\AbstractProvider;
use RuntimeException;

class ThrowingProvider extends AbstractProvider
{
    public function update($hostname, $ip)
    {
        throw new RuntimeException('boom');
    }
}
