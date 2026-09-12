<?php

declare(strict_types=1);

$repoRoot = dirname(__DIR__);
$configPath = $repoRoot . '/config.php';
$originalConfig = is_file($configPath) ? file_get_contents($configPath) : null;

register_shutdown_function(static function () use ($configPath, $originalConfig): void {
    if ($originalConfig === null) {
        @unlink($configPath);
        return;
    }

    file_put_contents($configPath, $originalConfig);
});

function assertSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function invokeEndpoint(array $config, string $bootstrap = '', array $server = array(), array $get = array()): array
{
    global $configPath, $repoRoot;

    file_put_contents($configPath, "<?php\nreturn " . var_export($config, true) . ";\n");

    $payload = var_export($get, true);
    $serverPayload = var_export(array_merge(array('REMOTE_ADDR' => '127.0.0.1'), $server), true);

    $script = $bootstrap . "\n"
        . '$_GET = ' . $payload . ";\n"
        . '$_POST = array();' . "\n"
        . '$_SERVER = ' . $serverPayload . ";\n"
        . 'ob_start(); include ' . var_export($repoRoot . '/ddns.php', true) . '; $body = ob_get_clean(); echo json_encode(array("code" => http_response_code(), "body" => $body));';

    $output = shell_exec('php -r ' . escapeshellarg($script));
    if ($output === null) {
        throw new RuntimeException('Failed to execute ddns.php test process');
    }

    $decoded = json_decode($output, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Unexpected test output: ' . $output);
    }

    return $decoded;
}

$passwordHash = password_hash('secret', PASSWORD_DEFAULT);
$baseConfig = array(
    'log_file' => $repoRoot . '/ddns.log',
    'hash_generator' => array('enabled' => false),
    'ip_fallback' => array('allow_remote_addr' => false),
    'domains' => array(
        'example.com' => array(
            'accounts' => array(
                'alice' => array(
                    'password_hash' => $passwordHash,
                    'hostnames' => array('home.example.com'),
                ),
            ),
        ),
    ),
);

$missingParams = invokeEndpoint($baseConfig);
assertSame(400, $missingParams['code'], 'Missing params should return 400.');
assertSame('FAIL', $missingParams['body'], 'Missing params should return FAIL.');

$unsupportedProvider = $baseConfig;
$unsupportedProvider['domains']['example.com']['provider'] = 'unsupported-provider';
$unsupported = invokeEndpoint($unsupportedProvider, '', array(), array(
    'username' => 'alice',
    'password' => 'secret',
    'hostname' => 'home.example.com',
    'ip' => '203.0.113.10',
));
assertSame(500, $unsupported['code'], 'Unsupported providers should return 500.');
assertSame('FAIL', $unsupported['body'], 'Unsupported providers should return FAIL.');

$exceptionProvider = $baseConfig;
$exceptionProvider['domains']['example.com']['provider'] = 'Tests\\Support\\ThrowingProvider';
$exception = invokeEndpoint(
    $exceptionProvider,
    'require ' . var_export(__DIR__ . '/support/ThrowingProvider.php', true) . ';',
    array(),
    array(
        'username' => 'alice',
        'password' => 'secret',
        'hostname' => 'home.example.com',
        'ip' => '203.0.113.10',
    )
);
assertSame(500, $exception['code'], 'Provider exceptions should return 500.');
assertSame('FAIL', $exception['body'], 'Provider exceptions should return FAIL.');

$failureProvider = $baseConfig;
$failureProvider['domains']['example.com']['provider'] = 'Tests\\Support\\FailingProvider';
$failure = invokeEndpoint(
    $failureProvider,
    'require ' . var_export(__DIR__ . '/support/FailingProvider.php', true) . ';',
    array(),
    array(
        'username' => 'alice',
        'password' => 'secret',
        'hostname' => 'home.example.com',
        'ip' => '203.0.113.10',
    )
);
assertSame(502, $failure['code'], 'Provider false results should return 502.');
assertSame('FAIL', $failure['body'], 'Provider false results should return FAIL.');

$basicAuthFailure = invokeEndpoint(
    $failureProvider,
    'require ' . var_export(__DIR__ . '/support/FailingProvider.php', true) . ';',
    array(
        'PHP_AUTH_USER' => 'alice',
        'PHP_AUTH_PW' => 'secret',
    ),
    array(
        'hostname' => 'home.example.com',
        'ip' => '203.0.113.10',
    )
);
assertSame(502, $basicAuthFailure['code'], 'Basic Auth requests should reach provider handling.');
assertSame('FAIL', $basicAuthFailure['body'], 'Basic Auth provider failures should return FAIL.');

$malformedPassword = invokeEndpoint($unsupportedProvider, '', array(), array(
    'username' => 'alice',
    'password' => array('secret'),
    'hostname' => 'home.example.com',
    'ip' => '203.0.113.10',
));
assertSame(400, $malformedPassword['code'], 'Array password input should be rejected.');
assertSame('FAIL', $malformedPassword['body'], 'Array password input should return FAIL.');

echo "All ddns endpoint tests passed.\n";
