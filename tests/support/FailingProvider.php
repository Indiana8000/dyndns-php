<?php

namespace Tests\Support;

require_once dirname(__DIR__, 2) . '/src/Autoloader.php';

use Dyndns\Providers\AbstractProvider;

class FailingProvider extends AbstractProvider
{
    public function update($hostname, $ip)
    {
        return false;
    }
}
