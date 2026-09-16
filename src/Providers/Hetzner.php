<?php

namespace Dyndns\Providers;

use RuntimeException;

class Hetzner extends AbstractProvider
{
    /** @var string */
    private $baseUrl = 'https://api.hetzner.cloud/v1';

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

        $rrset = $this->getRrset($zoneId, $recordName, $recordType);
        if ($rrset === null) {
            return false;
        }

        return $this->setRrsetRecords($zoneId, $recordName, $recordType, $ip, $rrset);
    }

    private function getZoneId()
    {
        $response = $this->hetznerRequest('GET', $this->baseUrl . '/zones?name=' . urlencode($this->domain));
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
     * @return array<string, mixed>|null
     */
    private function getRrset($zoneId, $name, $recordType)
    {
        $response = $this->hetznerRequest('GET', $this->baseUrl . '/zones/' . urlencode($zoneId) . '/rrsets/' . urlencode($name) . '/' . urlencode($recordType));
        if ($response['code'] === 404) {
            return null;
        }

        if ($response['code'] < 200 || $response['code'] >= 300) {
            throw new RuntimeException('Hetzner RRSet lookup failed with status ' . $response['code']);
        }

        $data = json_decode($response['body'], true);
        return $data['rrset'] ?? null;
    }

    /**
     * @param array<string, mixed> $rrset
     */
    private function setRrsetRecords($zoneId, $name, $recordType, $ip, array $rrset)
    {
        $body = $this->encodeJson(array(
            'records' => array(
                array(
                    'value' => $ip,
                    'comment' => '',
                ),
            ),
            'ttl' => $rrset['ttl'] ?? 60,
        ), 'Hetzner record payload');
        $url = $this->baseUrl . '/zones/' . urlencode($zoneId) . '/rrsets/' . urlencode($name) . '/' . urlencode($recordType) . '/actions/set_records';
        $response = $this->hetznerRequest('POST', $url, $body);

        return $response['code'] >= 200 && $response['code'] < 300;
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
                'Authorization: Bearer ' . $this->requireConfigValue('api_token'),
            ),
            $body
        );
    }
}
