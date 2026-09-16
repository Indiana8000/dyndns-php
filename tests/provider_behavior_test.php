<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Autoloader.php';

use Dyndns\HttpRequestInterface;
use Dyndns\Logger;
use Dyndns\Providers\Hetzner;
use Dyndns\Providers\InternetX;

function assertSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function assertThrows(callable $callback, $expectedException, $expectedMessage, $message)
{
    try {
        $callback();
    } catch (Throwable $throwable) {
        assertSame($expectedException, $throwable::class, $message . ' Wrong exception type.');
        assertSame($expectedMessage, $throwable->getMessage(), $message . ' Wrong exception message.');
        return;
    }

    throw new RuntimeException($message . ' Expected exception was not thrown.');
}

class FakeHttpRequest implements HttpRequestInterface
{
    /** @var array<int, array{code:int,body:string}> */
    public $responses;

    /** @var array<int, array{method:mixed,url:mixed,headers:array<int,string>,body:mixed}> */
    public $calls = array();

    /**
     * @param array<int, array{code:int,body:string}> $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function request($method, $url, array $headers = array(), $body = null)
    {
        $this->calls[] = array(
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        );

        if ($this->responses === array()) {
            throw new RuntimeException('No fake response configured');
        }

        return array_shift($this->responses);
    }
}

class FailingEncodeInternetX extends InternetX
{
    protected function encodeJson($value, $errorContext)
    {
        throw new RuntimeException('Failed to encode InternetX zone payload: forced test failure');
    }
}

class FailingEncodeHetzner extends Hetzner
{
    protected function encodeJson($value, $errorContext)
    {
        throw new RuntimeException('Failed to encode Hetzner record payload: forced test failure');
    }
}

$logger = new Logger(dirname(__DIR__) . '/ddns.log');

$internetXRequest = new FakeHttpRequest(array(
    array('code' => 200, 'body' => json_encode(array('main' => array('address' => '198.51.100.1')))),
    array('code' => 200, 'body' => '{}'),
));
$internetXProvider = new InternetX('example.com', array('api_token' => 'token'), $logger, $internetXRequest);
assertSame(true, $internetXProvider->update('example.com', '203.0.113.10'), 'InternetX should support root-only zone payloads.');
assertSame('PUT', $internetXRequest->calls[1]['method'], 'InternetX should issue a PUT request after reading the zone.');
assertSame('203.0.113.10', json_decode($internetXRequest->calls[1]['body'], true)['main']['address'], 'InternetX should update the root main address.');

$internetXNonRootMainOnlyRequest = new FakeHttpRequest(array(
    array('code' => 200, 'body' => json_encode(array('main' => array('address' => '198.51.100.1')))),
));
$internetXNonRootMainOnlyProvider = new InternetX('example.com', array('api_token' => 'token'), $logger, $internetXNonRootMainOnlyRequest);
assertSame(false, $internetXNonRootMainOnlyProvider->update('home.example.com', '203.0.113.10'), 'InternetX should reject main-only payloads for non-root hostnames.');

$internetXIpv6Request = new FakeHttpRequest(array(
    array('code' => 200, 'body' => json_encode(array(
        'main' => array('address' => '198.51.100.1'),
        'resourceRecords' => array(
            array('name' => '', 'type' => 'AAAA', 'value' => '2001:db8::1'),
        ),
    ))),
    array('code' => 200, 'body' => '{}'),
));
$internetXIpv6Provider = new InternetX('example.com', array('api_token' => 'token'), $logger, $internetXIpv6Request);
assertSame(true, $internetXIpv6Provider->update('example.com', '2001:db8::2'), 'InternetX should support IPv6 root updates through resource records.');
$internetXIpv6Payload = json_decode($internetXIpv6Request->calls[1]['body'], true);
assertSame('198.51.100.1', $internetXIpv6Payload['main']['address'], 'InternetX IPv6 root updates must not overwrite main.address.');
assertSame('2001:db8::2', $internetXIpv6Payload['resourceRecords'][0]['value'], 'InternetX IPv6 root updates should update the matching AAAA resource record.');

$internetXEncodeFailureRequest = new FakeHttpRequest(array(
    array('code' => 200, 'body' => json_encode(array('main' => array('address' => '198.51.100.1')))),
));
$internetXEncodeFailure = new FailingEncodeInternetX('example.com', array('api_token' => 'token'), $logger, $internetXEncodeFailureRequest);
assertThrows(
    static function () use ($internetXEncodeFailure): void {
        $internetXEncodeFailure->update('example.com', '203.0.113.10');
    },
    RuntimeException::class,
    'Failed to encode InternetX zone payload: forced test failure',
    'InternetX encode failures should bubble up as runtime exceptions.'
);

$hetznerCloudRequest = new FakeHttpRequest(array(
    array('code' => 200, 'body' => json_encode(array('zones' => array(array('name' => 'example.com', 'id' => 'zone-id'))))),
    array('code' => 200, 'body' => json_encode(array('rrset' => array('name' => 'home', 'type' => 'A', 'ttl' => 60, 'records' => array(array('value' => '198.51.100.1')))))),
    array('code' => 200, 'body' => json_encode(array('action' => array('status' => 'success')))),
));
$hetznerCloudProvider = new Hetzner('example.com', array('api_token' => 'token'), $logger, $hetznerCloudRequest);
assertSame(true, $hetznerCloudProvider->update('home.example.com', '203.0.113.10'), 'Hetzner should update records through the Cloud API.');
assertSame('https://api.hetzner.cloud/v1/zones?name=example.com', $hetznerCloudRequest->calls[0]['url'], 'Hetzner zone lookup should use the Cloud API endpoint.');
assertSame('Authorization: Bearer token', $hetznerCloudRequest->calls[0]['headers'][1], 'Hetzner should authenticate with the bearer token header.');
assertSame('GET', $hetznerCloudRequest->calls[1]['method'], 'Hetzner should look up the RRSet before updating it.');
assertSame('https://api.hetzner.cloud/v1/zones/zone-id/rrsets/home/A', $hetznerCloudRequest->calls[1]['url'], 'Hetzner should fetch the existing RRSet through the Cloud API.');
assertSame('POST', $hetznerCloudRequest->calls[2]['method'], 'Hetzner should use the set_records action to update an RRSet.');
assertSame('https://api.hetzner.cloud/v1/zones/zone-id/rrsets/home/A/actions/set_records', $hetznerCloudRequest->calls[2]['url'], 'Hetzner should update the RRSet via the Cloud API action endpoint.');
assertSame(array('value' => '203.0.113.10', 'comment' => ''), json_decode($hetznerCloudRequest->calls[2]['body'], true)['records'][0], 'Hetzner should send a precise A record payload to the Cloud API.');

$hetznerEncodeFailureRequest = new FakeHttpRequest(array(
    array('code' => 200, 'body' => json_encode(array('zones' => array(array('name' => 'example.com', 'id' => 'zone-id'))))),
    array('code' => 200, 'body' => json_encode(array('rrset' => array('name' => 'home', 'type' => 'A', 'ttl' => 60, 'records' => array(array('value' => '198.51.100.1')))))),
    array('code' => 200, 'body' => json_encode(array('action' => array('status' => 'success')))),
));
$hetznerEncodeFailure = new FailingEncodeHetzner('example.com', array('api_token' => 'token'), $logger, $hetznerEncodeFailureRequest);
assertThrows(
    static function () use ($hetznerEncodeFailure): void {
        $hetznerEncodeFailure->update('home.example.com', '203.0.113.10');
    },
    RuntimeException::class,
    'Failed to encode Hetzner record payload: forced test failure',
    'Hetzner encode failures should bubble up as runtime exceptions.'
);

echo "All provider behavior tests passed.\n";
