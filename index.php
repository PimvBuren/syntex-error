<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/server/classes/classDatabase.php';
require_once __DIR__ . '/server/classes/classShare.php';

$db    = new Database();
$conn  = $db->getConnection();
$share = new Share($conn);

$stmt = $conn->prepare("SELECT * FROM file WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$myFiles = $stmt->fetchAll();

$sharedFiles = $share->getFilesSharedWithMe($_SESSION['user_id']);

$uploadMessage = $_SESSION['upload_message'] ?? null;
$uploadStatus  = $_SESSION['upload_status']  ?? null;
unset($_SESSION['upload_message'], $_SESSION['upload_status']);

$errorMessages = [
    'ongeldig'     => 'Er is een ongeldig verzoek gedaan.',
    'toegang'      => 'Je hebt geen toegang tot dit bestand.',
    'nietgevonden' => 'Het bestand kon niet worden gevonden.',
    'integriteit'  => 'Het bestand is aangepast of beschadigd en kan niet worden gedownload.',
];
$downloadError = isset($_GET['error']) ? ($errorMessages[$_GET['error']] ?? null) : null;

function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1)    . ' KB';
    return $bytes . ' B';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syntax Error – Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body>

<nav>
    <span class="nav-brand">// syntax-error / dashboard</span>
    <div class="nav-right">
        <span><?= htmlspecialchars($_SESSION['username']) ?></span>
        <?php if ($_SESSION['role_id'] == 2): ?>
            <a href="admin.php">Beheer</a>
        <?php endif; ?>
        <a href="logout.php">Uitloggen</a>
    </div>
</nav>

<div class="page">

    <?php if ($uploadMessage): ?>
        <div class="alert alert-<?= $uploadStatus === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($uploadMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($downloadError): ?>
        <div class="alert alert-error"><?= htmlspecialchars($downloadError) ?></div>
    <?php endif; ?>

    <!-- Upload -->
    <div class="card">
        <h2>Bestand uploaden</h2>
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <div class="upload-zone">
                <input type="file" name="fileToUpload" id="fileToUpload" required>
                <button type="submit" name="submit" class="btn btn-primary">Uploaden</button>
            </div>
            <div class="form-group" style="margin-top:16px;">
                <label for="encrypt_key">Encryptiesleutel</label>
                <div style="display:flex; gap:8px; align-items:center;">
                    <input type="password" id="encrypt_key" name="encrypt_key"
                           placeholder="Kies een sleutel of genereer er een"
                           required style="flex:1;">
                    <button type="button" id="toggleKey" class="btn btn-outline"
                            onclick="toggleKeyVisibility()" style="white-space:nowrap;">
                        Toon
                    </button>
                    <button type="button" class="btn btn-outline"
                            onclick="generateKey()" style="white-space:nowrap;">
                        Genereer sleutel
                    </button>
                </div>
                <div id="keyDisplay" style="display:none; margin-top:10px; padding:10px 14px;
                     background:var(--bg); border:1px solid var(--border); border-radius:var(--radius);
                     font-family:var(--font-mono); font-size:13px; color:var(--text);
                     align-items:center; justify-content:space-between; gap:12px;">
                    <span id="keyText" style="word-break:break-all;"></span>
                    <button type="button" onclick="copyKey()" class="btn btn-outline"
                            style="padding:6px 12px; font-size:12px; flex-shrink:0;">
                        Kopieer
                    </button>
                </div>
                <p id="copiedMsg" style="display:none; margin-top:6px; font-size:12px; color:var(--green);">
                    Sleutel gekopieerd! Stuur hem via Snapchat naar de ontvanger.
                </p>
            </div>
            <p style="font-size:13px; color:var(--muted); margin-top:8px;">
                Toegestaan: JPG, PNG, GIF · Max. 500 KB · Deel de sleutel via Snapchat met de ontvanger
            </p>
        </form>
    </div>

    <!-- Eigen bestanden -->
    <div class="card">
        <h2>Jouw bestanden</h2>
        <?php if (empty($myFiles)): ?>
            <div class="empty-state"><p>Nog geen bestanden geüpload.</p></div>
        <?php else: ?>
            <table class="file-table">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Bestandsnaam</th>
                        <th>Grootte</th>
                        <th>Geüpload op</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myFiles as $file): ?>
                    <tr>
                        <td>
                            <img class="file-thumb"
                                 src="server/uploads/<?= htmlspecialchars($file['filename']) ?>"
                                 alt="preview">
                        </td>
                        <td><span class="file-name"><?= htmlspecialchars($file['filename']) ?></span></td>
                        <td class="file-meta"><?= formatBytes((int)$file['file_size']) ?></td>
                        <td class="file-meta"><?= htmlspecialchars($file['uploaded_at']) ?></td>
                        <td style="display:flex; gap:8px; padding:12px 14px;">
                            <a href="download.php?id=<?= $file['file_id'] ?>" class="btn btn-outline">Download</a>
                            <a href="share.php?file_id=<?= $file['file_id'] ?>" class="btn btn-outline">Delen</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Gedeeld met mij -->
    <?php if (!empty($sharedFiles)): ?>
    <div class="card">
        <h2>Gedeeld met jou</h2>
        <table class="file-table">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Bestandsnaam</th>
                    <th>Eigenaar</th>
                    <th>Grootte</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sharedFiles as $file): ?>
                <tr>
                    <td>
                        <img class="file-thumb"
                             src="server/uploads/<?= htmlspecialchars($file['filename']) ?>"
                             alt="preview">
                    </td>
                    <td><span class="file-name"><?= htmlspecialchars($file['filename']) ?></span></td>
                    <td class="file-meta"><?= htmlspecialchars($file['owner_name']) ?></td>
                    <td class="file-meta"><?= formatBytes((int)$file['file_size']) ?></td>
                    <td>
                        <a href="download.php?id=<?= $file['file_id'] ?>" class="btn btn-outline">Download</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<script src="script.js"></script>
<script>
// Genereer een willekeurige sleutel van 20 tekens
function generateKey() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    const array = new Uint8Array(20);
    window.crypto.getRandomValues(array);
    const key = Array.from(array).map(b => chars[b % chars.length]).join('');

    // Vul het invoerveld in
    document.getElementById('encrypt_key').value = key;
    document.getElementById('encrypt_key').type  = 'text';
    document.getElementById('toggleKey').textContent = 'Verberg';

    // Toon de sleutel in het display blok
    document.getElementById('keyText').textContent = key;
    document.getElementById('keyDisplay').style.display = 'flex';
    document.getElementById('copiedMsg').style.display = 'none';
}

// Kopieer de sleutel naar het klembord
function copyKey() {
    const key = document.getElementById('keyText').textContent;
    navigator.clipboard.writeText(key).then(() => {
        document.getElementById('copiedMsg').style.display = 'block';
        setTimeout(() => {
            document.getElementById('copiedMsg').style.display = 'none';
        }, 4000);
    });
}

// Toon/verberg het wachtwoordveld
function toggleKeyVisibility() {
    const input  = document.getElementById('encrypt_key');
    const btn    = document.getElementById('toggleKey');
    if (input.type === 'password') {
        input.type   = 'text';
        btn.textContent = 'Verberg';
    } else {
        input.type   = 'password';
        btn.textContent = 'Toon';
    }
}
</script>
</html>