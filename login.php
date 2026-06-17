<?php
session_start();

require_once __DIR__ . '/server/classes/classDatabase.php';
require_once __DIR__ . '/server/classes/classUser.php';
require_once __DIR__ . '/server/classes/classLog.php';

$database  = new Database();
$conn      = $database->getConnection();
$userClass = new User($conn);
$logger    = new Log($conn);

$error   = '';
$success = '';
$mode    = $_POST['mode'] ?? $_GET['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if ($mode === 'register') {
        if (empty($username) || empty($email) || empty($password)) {
            $error = "Vul alle velden in.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Voer een geldig e-mailadres in.";
        } elseif (strlen($password) < 8) {
            $error = "Wachtwoord moet minimaal 8 tekens bevatten.";
        } else {
            $result = $userClass->register($username, $email, $password);
            if ($result === true) {
                // Log de registratie
                $newUser = $conn->prepare("SELECT user_id FROM user WHERE username = ?");
                $newUser->execute([$username]);
                $newUserId = $newUser->fetchColumn();
                $logger->log('register', $newUserId, null, "Nieuw account: $username");

                $success = "Account aangemaakt! Je kunt nu inloggen.";
                $mode = 'login';
            } else {
                $error = $result;
            }
        }
    } else {
        if (empty($username) || empty($password)) {
            $error = "Vul alle velden in.";
        } else {
            $result = $userClass->login($username, $password);
            if (is_array($result)) {
                $_SESSION['user_id']  = $result['user_id'];
                $_SESSION['username'] = $result['username'];
                $_SESSION['role_id']  = $result['role_id'];

                // Log de login
                $logger->log('login', $result['user_id'], null, "Ingelogd: $username");

                header("Location: index.php");
                exit;
            } else {
                // Log mislukte loginpoging
                $logger->log('login_failed', null, null, "Mislukte login voor: $username");
                $error = $result;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syntax Error – <?= $mode === 'register' ? 'Registreren' : 'Inloggen' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-box">

        <div class="logo">// syntax-error</div>
        <h1><?= $mode === 'register' ? 'Account aanmaken' : 'Inloggen' ?></h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <input type="hidden" name="mode" value="<?= $mode === 'register' ? 'register' : 'login' ?>">

            <div class="form-group">
                <label for="username">Gebruikersnaam</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       required autocomplete="username">
            </div>

            <?php if ($mode === 'register'): ?>
            <div class="form-group">
                <label for="email">E-mailadres</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="password">Wachtwoord</label>
                <input type="password" id="password" name="password"
                       required autocomplete="<?= $mode === 'register' ? 'new-password' : 'current-password' ?>">
            </div>

            <button type="submit" class="btn btn-primary form-submit">
                <?= $mode === 'register' ? 'Account aanmaken' : 'Inloggen' ?>
            </button>
        </form>

        <p class="switch-mode">
            <?php if ($mode === 'register'): ?>
                Al een account? <a href="login.php">Inloggen</a>
            <?php else: ?>
                Nog geen account? <a href="login.php?mode=register">Registreren</a>
            <?php endif; ?>
        </p>

    </div>
</div>
</body>
</html>