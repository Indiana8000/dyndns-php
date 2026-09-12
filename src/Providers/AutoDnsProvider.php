<?php

namespace Dyndns\Providers;

class AutoDnsProvider extends InternetX
{
    protected function getContextId()
    {
        if (isset($this->config['context']) && $this->config['context'] !== '') {
            return (int) $this->config['context'];
        }

        return 10;
    }
}
