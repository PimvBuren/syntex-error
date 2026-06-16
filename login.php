<?php
session_start();

require_once __DIR__ . '/classes/classDatabase.php';
require_once __DIR__ . '/classes/classUser.php';

$database = new Database();
$conn = $database->getConnection();
$userClass = new User($conn);

$error   = '';
$success = '';
$mode    = $_POST['mode'] ?? $_GET['mode'] ?? 'login'; // 'login' of 'register'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if ($mode === 'register') {
        // --- Registratie ---
        if (empty($username) || empty($email) || empty($password)) {
            $error = "Vul alle velden in.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Voer een geldig e-mailadres in.";
        } elseif (strlen($password) < 8) {
            $error = "Wachtwoord moet minimaal 8 tekens bevatten.";
        } else {
            $result = $userClass->register($username, $email, $password);
            if ($result === true) {
                $success = "Account aangemaakt! Je kunt nu inloggen.";
                $mode = 'login';
            } else {
                $error = $result;
            }
        }
    } else {
        // --- Login ---
        if (empty($username) || empty($password)) {
            $error = "Vul alle velden in.";
        } else {
            $result = $userClass->login($username, $password);
            if (is_array($result)) {
                // Sessie starten
                $_SESSION['user_id']  = $result['user_id'];
                $_SESSION['username'] = $result['username'];
                $_SESSION['role_id']  = $result['role_id'];
                header("Location: index.php");
                exit;
            } else {
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
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body>

<div class="auth-container">
    <h1><?= $mode === 'register' ? 'Registreren' : 'Inloggen' ?></h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <input type="hidden" name="mode" value="<?= $mode === 'register' ? 'register' : 'login' ?>">

        <label for="username">Gebruikersnaam</label>
        <input type="text" id="username" name="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               required autocomplete="username">

        <?php if ($mode === 'register'): ?>
        <label for="email">E-mailadres</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autocomplete="email">
        <?php endif; ?>

        <label for="password">Wachtwoord</label>
        <input type="password" id="password" name="password"
               required autocomplete="<?= $mode === 'register' ? 'new-password' : 'current-password' ?>">

        <button type="submit">
            <?= $mode === 'register' ? 'Account aanmaken' : 'Inloggen' ?>
        </button>
    </form>

    <p class="switch-mode">
        <?php if ($mode === 'register'): ?>
            Al een account?
            <a href="login.php">Inloggen</a>
        <?php else: ?>
            Nog geen account?
            <a href="login.php?mode=register">Registreren</a>
        <?php endif; ?>
    </p>
</div>

<script src="script.js"></script>
</body>
</html>