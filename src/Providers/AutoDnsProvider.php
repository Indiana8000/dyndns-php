<?php

namespace Dyndns\Providers;

class AutoDnsProvider extends AbstractProvider
{
    public function update($hostname, $ip)
    {
        $zone = $this->getZone();
        if ($zone === null) {
            return false;
        }

        $relativeHostname = $this->getRelativeHostname($hostname);
        if ($relativeHostname === null) {
            return false;
        }

        if ($relativeHostname === '@') {
            $zone['main']['address'] = $ip;
        } else {
            $zone = $this->updateZone($zone, $relativeHostname, $ip);
            if ($zone === null) {
                return false;
            }
        }

        return $this->putZone($zone);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getZone()
    {
        $response = $this->autoDnsRequest('GET', 'https://api.autodns.com/v1/zone/' . $this->domain);
        $data = json_decode($response['body'], true);

        return $data['data'][0] ?? null;
    }

    /**
     * @param array<string, mixed> $zone
     * @return array<string, mixed>|null
     */
    private function updateZone(array $zone, $hostname, $ip)
    {
        foreach (($zone['resourceRecords'] ?? array()) as $index => $record) {
            if (($record['name'] ?? null) === $hostname && ($record['type'] ?? null) === 'A') {
                $zone['resourceRecords'][$index]['value'] = $ip;
                return $zone;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $zone
     */
    private function putZone(array $zone)
    {
        $body = json_encode($zone);
        $body = str_replace(',"purgeType":"AUTO"', '', $body);
        $response = $this->autoDnsRequest('PUT', 'https://api.autodns.com/v1/zone/' . $this->domain, $body);

        return $response['code'] >= 200 && $response['code'] < 300;
    }

    /**
     * @return array{code:int,body:string}
     */
    private function autoDnsRequest($method, $url, $body = null)
    {
        return $this->httpClient->request(
            $method,
            $url,
            array(
                'Content-Type: application/json',
                'Authorization: Basic ' . $this->requireConfigValue('api_token'),
                'X-Domainrobot-Context: ' . (string) ($this->config['context'] ?? 10),
            ),
            $body
        );
    }
}
