<?php

namespace Dyndns\Providers;

class HetznerProvider extends AbstractProvider
{
    public function update($hostname, $ip)
    {
        $zoneId = $this->getZoneId();
        if ($zoneId === false) {
            return false;
        }

        $recordName = $this->getRelativeHostname($hostname);
        if ($recordName === null || !$this->rrsetExists($zoneId, $recordName)) {
            return false;
        }

        return $this->setRrsetRecords($zoneId, $recordName, $ip);
    }

    private function getZoneId()
    {
        $response = $this->hetznerRequest('GET', 'https://api.hetzner.cloud/v1/zones?name=' . urlencode($this->domain));
        $data = json_decode($response['body'], true);

        return $data['zones'][0]['id'] ?? false;
    }

    private function rrsetExists($zoneId, $name)
    {
        $response = $this->hetznerRequest('GET', 'https://api.hetzner.cloud/v1/zones/' . urlencode($zoneId) . '/rrsets/' . urlencode($name) . '/A');
        return $response['code'] === 200;
    }

    private function setRrsetRecords($zoneId, $name, $ip)
    {
        $body = json_encode(array(
            'records' => array(array('value' => $ip, 'comment' => '')),
        ));
        $url = 'https://api.hetzner.cloud/v1/zones/' . urlencode($zoneId) . '/rrsets/' . urlencode($name) . '/A/actions/set_records';
        $response = $this->hetznerRequest('POST', $url, $body);

        return $response['code'] === 201;
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
                'Authorization: Bearer ' . $this->requireConfigValue('api_token'),
            ),
            $body
        );
    }
}
