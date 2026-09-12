<?php

namespace Dyndns\Providers;

use RuntimeException;

class AutoDnsProvider extends InternetX implements ProviderInterface
{
    protected function getContextId()
    {
        if (isset($this->config['context']) && $this->config['context'] !== '') {
            $rawContext = $this->config['context'];
            if ($rawContext === 4 || $rawContext === '4') {
                return 4;
            }

            if ($rawContext === 10 || $rawContext === '10') {
                return 10;
            }

            throw new RuntimeException('Unsupported legacy autodns context: ' . $rawContext);
        }

        return 10;
    }
}
