<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/server/classes/classDatabase.php';

$db   = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM file WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$files = $stmt->fetchAll();

$uploadMessage = $_SESSION['upload_message'] ?? null;
$uploadStatus  = $_SESSION['upload_status']  ?? null;
unset($_SESSION['upload_message'], $_SESSION['upload_status']);

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
    <link rel="stylesheet" href="client/stylesheet.css">
</head>
<body>

<nav>
    <span class="nav-brand">// syntax-error / dashboard</span>
    <div class="nav-right">
        <span><?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="logout.php">Uitloggen</a>
    </div>
</nav>

<div class="page">

    <?php if ($uploadMessage): ?>
        <div class="alert alert-<?= $uploadStatus === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($uploadMessage) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Bestand uploaden</h2>
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <div class="upload-zone">
                <input type="file" name="fileToUpload" id="fileToUpload" required>
                <button type="submit" name="submit" class="btn btn-primary">Uploaden</button>
            </div>
            <p style="margin-top:10px; font-size:13px; color:var(--muted);">
                Toegestaan: JPG, PNG, GIF · Max. 500 KB
            </p>
        </form>
    </div>

    <div class="card">
        <h2>Jouw bestanden</h2>

        <?php if (empty($files)): ?>
            <div class="empty-state">
                <p>Nog geen bestanden geüpload.</p>
            </div>
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
                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td>
                            <img class="file-thumb"
                                 src="server/uploads/<?= htmlspecialchars($file['filename']) ?>"
                                 alt="preview">
                        </td>
                        <td><span class="file-name"><?= htmlspecialchars($file['filename']) ?></span></td>
                        <td class="file-meta"><?= formatBytes((int)$file['file_size']) ?></td>
                        <td class="file-meta"><?= htmlspecialchars($file['uploaded_at']) ?></td>
                        <td>
                            <a href="download.php?id=<?= $file['file_id'] ?>" class="btn btn-outline">
                                Download
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

<script src="client/script.js"></script>
</body>
</html>