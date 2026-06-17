<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/server/classes/classDatabase.php';
require_once __DIR__ . '/server/classes/classShare.php';
require_once __DIR__ . '/server/classes/classLog.php';

$id      = $_GET['id']          ?? null;
$userKey = $_POST['decrypt_key'] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: index.php?error=ongeldig");
    exit;
}

$db     = new Database();
$conn   = $db->getConnection();
$share  = new Share($conn);
$logger = new Log($conn);

// Toegangscontrole
if (!$share->hasAccess((int)$id, $_SESSION['user_id'])) {
    $logger->log('download_denied', $_SESSION['user_id'], (int)$id, "Geen toegang tot bestand #$id");
    header("Location: index.php?error=toegang");
    exit;
}

$stmt = $conn->prepare("SELECT filename, file_hash, file_type FROM file WHERE file_id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    header("Location: index.php?error=nietgevonden");
    exit;
}

// Als er nog geen sleutel is ingevuld: toon de popup
if (!$userKey) {
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sleutel invoeren</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="stylesheet.css">
    </head>
    <body>
    <div class="auth-wrapper">
        <div class="auth-box">
            <div class="logo">// syntax-error</div>
            <h1>Sleutel invoeren</h1>
            <p class="file-meta" style="margin-bottom:24px;">
                Voer de encryptiesleutel in die de eigenaar met je heeft gedeeld om
                <strong style="color:var(--text)"><?= htmlspecialchars($file['filename']) ?></strong>
                te downloaden.
            </p>

            <form method="post" action="download.php?id=<?= (int)$id ?>">
                <div class="form-group">
                    <label for="decrypt_key">Encryptiesleutel</label>
                    <input type="password" id="decrypt_key" name="decrypt_key"
                           placeholder="Voer de sleutel in..." required autofocus>
                </div>
                <button type="submit" class="btn btn-primary form-submit">
                    Ontsleutelen en downloaden
                </button>
            </form>

            <p class="switch-mode" style="margin-top:16px;">
                <a href="index.php">Terug naar dashboard</a>
            </p>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Sleutel is ingevuld, probeer te ontsleutelen
$path = __DIR__ . '/server/uploads/' . $file['filename'];

if (!file_exists($path)) {
    $logger->log('download_failed', $_SESSION['user_id'], (int)$id, "Bestand niet op server");
    header("Location: index.php?error=nietgevonden");
    exit;
}

$encryptedData = file_get_contents($path);

// Ontsleutel: eerste 16 bytes = IV, rest = versleutelde data
$key           = hash('sha256', $userKey, true);
$iv            = substr($encryptedData, 0, 16);
$encrypted     = substr($encryptedData, 16);
$decryptedData = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

if ($decryptedData === false) {
    // Verkeerde sleutel
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sleutel invoeren</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="stylesheet.css">
    </head>
    <body>
    <div class="auth-wrapper">
        <div class="auth-box">
            <div class="logo">// syntax-error</div>
            <h1>Sleutel invoeren</h1>

            <div class="alert alert-error">Verkeerde sleutel. Probeer het opnieuw.</div>

            <form method="post" action="download.php?id=<?= (int)$id ?>">
                <div class="form-group">
                    <label for="decrypt_key">Encryptiesleutel</label>
                    <input type="password" id="decrypt_key" name="decrypt_key"
                           placeholder="Voer de sleutel in..." required autofocus>
                </div>
                <button type="submit" class="btn btn-primary form-submit">
                    Ontsleutelen en downloaden
                </button>
            </form>

            <p class="switch-mode" style="margin-top:16px;">
                <a href="index.php">Terug naar dashboard</a>
            </p>
        </div>
    </div>
    </body>
    </html>
    <?php
    $logger->log('decrypt_failed', $_SESSION['user_id'], (int)$id, "Verkeerde sleutel voor: " . $file['filename']);
    exit;
}

// Integriteitscontrole
$currentHash = hash('sha256', $decryptedData);
if (!hash_equals($file['file_hash'], $currentHash)) {
    $logger->log('integrity_failed', $_SESSION['user_id'], (int)$id, "Hash klopt niet: " . $file['filename']);
    header("Location: index.php?error=integriteit");
    exit;
}

// Alles klopt, log en stuur het bestand
$logger->log('download', $_SESSION['user_id'], (int)$id, "Gedownload: " . $file['filename']);

header('Content-Type: ' . $file['file_type']);
header('Content-Disposition: attachment; filename="' . basename($file['filename']) . '"');
header('Content-Length: ' . strlen($decryptedData));
echo $decryptedData;
exit;