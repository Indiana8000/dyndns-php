<?php

namespace Dyndns\Providers;

interface ProviderInterface
{
    public function update($hostname, $ip);
}
