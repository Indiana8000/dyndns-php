<?php

namespace Dyndns\Providers;

use RuntimeException;

class AutoDnsProvider extends InternetX implements ProviderInterface
{
    protected function getContextId()
    {
        if (isset($this->config['context']) && $this->config['context'] !== '') {
            $rawContext = $this->config['context'];
            if (is_int($rawContext) || (is_string($rawContext) && preg_match('/^[0-9]+$/', $rawContext) === 1)) {
                $normalizedContext = (int) $rawContext;
                if (in_array($normalizedContext, array(4, 10), true)) {
                    return $normalizedContext;
                }
            }

            throw new RuntimeException('Unsupported legacy autodns context: ' . $rawContext);
        }

        return 10;
    }
}
