<?php

namespace Dyndns\Providers;

use RuntimeException;

class Hetzner extends AbstractProvider
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
        $recordType = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'AAAA' : 'A';
        if ($recordName === null) {
            return false;
        }

        $record = $this->getRecord($zoneId, $recordName, $recordType);
        if ($record === null) {
            return false;
        }

        return $this->setRrsetRecords($zoneId, $record, $recordName, $recordType, $ip);
    }

    private function getZoneId()
    {
        $response = $this->hetznerRequest('GET', $this->baseUrl . '/zones');
        if ($response['code'] < 200 || $response['code'] >= 300) {
            throw new RuntimeException('Hetzner zone lookup failed with status ' . $response['code']);
        }

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
    private function setRrsetRecords($zoneId, array $record, $name, $recordType, $ip)
    {
        if (empty($record['id'])) {
            return false;
        }

        $body = json_encode(array(
            'zone_id' => $zoneId,
            'type' => $recordType,
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
    private function getRecord($zoneId, $name, $recordType)
    {
        $response = $this->hetznerRequest('GET', $this->baseUrl . '/records?zone_id=' . urlencode($zoneId));
        if ($response['code'] < 200 || $response['code'] >= 300) {
            throw new RuntimeException('Hetzner record lookup failed with status ' . $response['code']);
        }

        $data = json_decode($response['body'], true);
        $expectedName = $name === '@' ? '' : $name;

        foreach (($data['records'] ?? array()) as $record) {
            if (($record['type'] ?? null) === $recordType && ($record['name'] ?? null) === $expectedName) {
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
        return $this->httpRequest->request(
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
