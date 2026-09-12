<?php

namespace Dyndns\Providers;

use RuntimeException;

class AutoDnsProvider extends InternetX implements ProviderInterface
{
    protected function getContextId()
    {
        if (isset($this->config['context']) && $this->config['context'] !== '') {
            $context = (int) $this->config['context'];
            if (in_array($context, array(4, 10), true)) {
                return $context;
            }

            throw new RuntimeException('Unsupported legacy autodns context: ' . $context);
        }

        return 10;
    }
}
