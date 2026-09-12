<?php

$hash = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !empty($_POST['password'])) {
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
    <input type="password" name="password" placeholder="Neues Passwort" required>
    <button type="submit">Hash erzeugen</button>
</form>
<?php if ($hash !== null): ?>
<pre><?= htmlspecialchars($hash, ENT_QUOTES, 'UTF-8') ?></pre>
<?php endif; ?>
</body>
</html>
