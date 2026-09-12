<?php

namespace Dyndns\Providers;

class HetznerProvider extends AbstractProvider
{
    /** @var string */
    private $baseUrl = 'https://dns.hetzner.com/api/v1';

    public function update($hostname, $ip)
    {
        $zoneId = $this->getZoneId();
        if ($zoneId === false) {
            return false;
        }

        $recordName = $this->getRelativeHostname($hostname);
        if ($recordName === null) {
            return false;
        }

        $record = $this->getRecord($zoneId, $recordName);
        if ($record === null) {
            return false;
        }

        return $this->setRrsetRecords($zoneId, $record, $recordName, $ip);
    }

    private function getZoneId()
    {
        $response = $this->hetznerRequest('GET', $this->baseUrl . '/zones');
        $data = json_decode($response['body'], true);

        foreach (($data['zones'] ?? array()) as $zone) {
            if (($zone['name'] ?? null) === $this->domain) {
                return $zone['id'] ?? false;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function setRrsetRecords($zoneId, array $record, $name, $ip)
    {
        if (empty($record['id'])) {
            return false;
        }

        $body = json_encode(array(
            'zone_id' => $zoneId,
            'type' => 'A',
            'name' => $name === '@' ? '' : $name,
            'value' => $ip,
            'ttl' => $record['ttl'] ?? 60,
        ));
        $url = $this->baseUrl . '/records/' . urlencode($record['id']);
        $response = $this->hetznerRequest('PUT', $url, $body);

        return $response['code'] >= 200 && $response['code'] < 300;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRecord($zoneId, $name)
    {
        $response = $this->hetznerRequest('GET', $this->baseUrl . '/records?zone_id=' . urlencode($zoneId));
        $data = json_decode($response['body'], true);
        $expectedName = $name === '@' ? '' : $name;

        foreach (($data['records'] ?? array()) as $record) {
            if (($record['type'] ?? null) === 'A' && ($record['name'] ?? null) === $expectedName) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @return array{code:int,body:string}
     */
    private function hetznerRequest($method, $url, $body = null)
    {
        return $this->httpClient->request(
            $method,
            $url,
            array(
                'Content-Type: application/json',
                'Auth-API-Token: ' . $this->requireConfigValue('api_token'),
            ),
            $body
        );
    }
}
