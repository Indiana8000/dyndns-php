<?php

namespace Dyndns\Providers;

use RuntimeException;

class InternetX extends AbstractProvider
{
    public function update($hostname, $ip)
    {
        $zone = $this->getZone();
        if ($zone === null) {
            return false;
        }

        $relativeHostname = $this->getRelativeHostname($hostname);
        $recordType = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'AAAA' : 'A';
        if ($relativeHostname === null) {
            return false;
        }

        $zone = $this->updateZone($zone, $relativeHostname, $recordType, $ip);
        if ($zone === null) {
            return false;
        }

        return $this->putZone($zone);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getZone()
    {
        $response = $this->internetXRequest('GET', 'https://api.autodns.com/v1/zone/' . $this->domain);
        if ($response['code'] < 200 || $response['code'] >= 300) {
            return null;
        }

        $data = json_decode($response['body'], true);

        if (
            isset($data['data'][0])
            && is_array($data['data'][0])
            && (isset($data['data'][0]['resourceRecords']) || isset($data['data'][0]['main']))
        ) {
            return $data['data'][0];
        }

        if (
            isset($data['data'])
            && is_array($data['data'])
            && (isset($data['data']['resourceRecords']) || isset($data['data']['main']))
        ) {
            return $data['data'];
        }

        if (is_array($data) && (isset($data['resourceRecords']) || isset($data['main']))) {
            return $data;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $zone
     * @return array<string, mixed>|null
     */
    private function updateZone(array $zone, $hostname, $recordType, $ip)
    {
        $updated = false;

        if ($hostname === '@' && $recordType === 'A' && isset($zone['main']) && is_array($zone['main'])) {
            $zone['main']['address'] = $ip;
            $updated = true;
        }

        foreach (($zone['resourceRecords'] ?? array()) as $index => $record) {
            $recordName = $record['name'] ?? null;
            $matchesRoot = $hostname === '@' && ($recordName === '' || $recordName === '@');
            if (($matchesRoot || $recordName === $hostname) && ($record['type'] ?? null) === $recordType) {
                $zone['resourceRecords'][$index]['value'] = $ip;
                $updated = true;
            }
        }

        return $updated ? $zone : null;
    }

    /**
     * @param array<string, mixed> $zone
     */
    private function putZone(array $zone)
    {
        unset($zone['purgeType']);
        $body = $this->encodeJson($zone, 'InternetX zone payload');
        $response = $this->internetXRequest('PUT', 'https://api.autodns.com/v1/zone/' . $this->domain, $body);

        return $response['code'] >= 200 && $response['code'] < 300;
    }

    protected function getContextId()
    {
        return 4;
    }

    /**
     * @return array{code:int,body:string}
     */
    private function internetXRequest($method, $url, $body = null)
    {
        return $this->httpRequest->request(
            $method,
            $url,
            array(
                'Content-Type: application/json',
                'Authorization: Basic ' . $this->requireConfigValue('api_token'),
                'X-Domainrobot-Context: ' . (string) $this->getContextId(),
            ),
            $body
        );
    }
}
