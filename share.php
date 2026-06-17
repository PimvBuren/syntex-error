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

$action  = $_POST['action']   ?? null;
$fileId  = (int)($_POST['file_id'] ?? $_GET['file_id'] ?? 0);
$message = '';
$status  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'share') {
        $targetUsername = trim($_POST['username'] ?? '');
        if (empty($targetUsername)) {
            $message = "Voer een gebruikersnaam in.";
            $status  = 'error';
        } else {
            $message = $share->shareFile($fileId, $_SESSION['user_id'], $targetUsername);
            $status  = str_contains($message, 'gedeeld met') ? 'success' : 'error';
        }
    } elseif ($action === 'unshare') {
        $sharedWithId = (int)($_POST['shared_with_id'] ?? 0);
        $message = $share->unshareFile($fileId, $_SESSION['user_id'], $sharedWithId);
        $status  = 'success';
    }
}

// Haal bestandsinfo op (alleen als eigenaar)
$stmt = $conn->prepare("SELECT * FROM file WHERE file_id = ? AND user_id = ?");
$stmt->execute([$fileId, $_SESSION['user_id']]);
$file = $stmt->fetch();

if (!$file) {
    header("Location: index.php");
    exit;
}

$sharedWith = $share->getSharedWith($fileId, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syntax Error – Delen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body>

<nav>
    <span class="nav-brand">// syntax-error / delen</span>
    <div class="nav-right">
        <span><?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="index.php">Terug</a>
        <a href="logout.php">Uitloggen</a>
    </div>
</nav>

<div class="page">

    <?php if ($message): ?>
        <div class="alert alert-<?= $status ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Bestand delen</h2>
        <p class="file-meta" style="margin-bottom:20px;">
            Bestand: <span class="file-name"><?= htmlspecialchars($file['filename']) ?></span>
        </p>

        <form method="post" action="share.php">
            <input type="hidden" name="action" value="share">
            <input type="hidden" name="file_id" value="<?= $file['file_id'] ?>">
            <div class="upload-zone">
                <input type="text" name="username" placeholder="Gebruikersnaam"
                       style="flex:1; padding:10px 14px; background:var(--bg); border:1px solid var(--border);
                              border-radius:var(--radius); color:var(--text); font-size:14px;"
                       required>
                <button type="submit" class="btn btn-primary">Delen</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Gedeeld met</h2>

        <?php if (empty($sharedWith)): ?>
            <div class="empty-state">
                <p>Dit bestand is nog niet gedeeld.</p>
            </div>
        <?php else: ?>
            <table class="file-table">
                <thead>
                    <tr>
                        <th>Gebruiker</th>
                        <th>Gedeeld op</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sharedWith as $s): ?>
                    <tr>
                        <td><span class="file-name"><?= htmlspecialchars($s['username']) ?></span></td>
                        <td class="file-meta"><?= htmlspecialchars($s['shared_at']) ?></td>
                        <td>
                            <form method="post" action="share.php" style="display:inline;">
                                <input type="hidden" name="action" value="unshare">
                                <input type="hidden" name="file_id" value="<?= $file['file_id'] ?>">
                                <input type="hidden" name="shared_with_id" value="<?= $s['user_id'] ?>">
                                <button type="submit" class="btn btn-outline"
                                        onclick="return confirm('Toegang intrekken van <?= htmlspecialchars($s['username']) ?>?')">
                                    Intrekken
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
</body>
</html>