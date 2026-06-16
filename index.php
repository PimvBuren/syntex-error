<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'classes/classDatabase.php';

$db = new Database();
$conn = $db->getConnection();

// Haal alleen de bestanden op van de ingelogde gebruiker
$stmt = $conn->prepare("SELECT * FROM file WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$files = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syntax Error – Dashboard</title>
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body>

<nav>
    <span>Welkom, <?= htmlspecialchars($_SESSION['username']) ?></span>
    <a href="logout.php">Uitloggen</a>
</nav>

<h1>Bestanden uploaden</h1>

<form action="upload.php" method="post" enctype="multipart/form-data">
    <input type="file" name="fileToUpload" id="fileToUpload" required>
    <input type="submit" value="Uploaden" name="submit">
</form>

<h2>Jouw uploads</h2>

<?php if (empty($files)): ?>
    <p>Je hebt nog geen bestanden geüpload.</p>
<?php else: ?>
    <?php foreach ($files as $file): ?>
        <div class="file-item">
            <img src="uploads/<?= htmlspecialchars($file['filename']) ?>" width="150" alt="<?= htmlspecialchars($file['filename']) ?>">
            <p><?= htmlspecialchars($file['filename']) ?></p>
            <a href="download.php?id=<?= $file['file_id'] ?>">Download</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script src="script.js"></script>
</body>
</html>