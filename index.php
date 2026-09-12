<?php

require_once __DIR__ . '/src/Autoloader.php';

$config = Dyndns\Config::load(__DIR__);
if (
    PHP_SAPI !== 'cli'
    && (
        !$config->isHashGeneratorEnabled()
        || !in_array($_SERVER['REMOTE_ADDR'] ?? '', array('127.0.0.1', '::1'), true)
    )
) {
    http_response_code(403);
    print 'Forbidden';
    return;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

$hash = null;
if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && hash_equals($csrfToken, (string) ($_POST['csrf_token'] ?? ''))
    && !empty($_POST['password'])
) {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>DDNS Passwort-Hash generieren</title>
</head>
<body>
<h1>Passwort-Hash generieren</h1>
<form method="post">
    <label for="password">Neues Passwort</label>
    <input id="password" type="password" name="password" placeholder="Neues Passwort" required>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit">Hash erzeugen</button>
</form>
<?php if ($hash !== null): ?>
<pre><?= htmlspecialchars($hash, ENT_QUOTES, 'UTF-8') ?></pre>
<?php endif; ?>
</body>
</html>
