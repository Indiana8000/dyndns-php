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

$hetznerEncodeFailureRequest = new FakeHttpRequest(array(
    array('code' => 200, 'body' => json_encode(array('zones' => array(array('name' => 'example.com', 'id' => 'zone-id'))))),
    array('code' => 200, 'body' => json_encode(array('records' => array(array('id' => 'record-id', 'type' => 'A', 'name' => 'home', 'ttl' => 60))))),
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
