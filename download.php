<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/server/classes/classDatabase.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: index.php?error=ongeldig");
    exit;
}

$db   = new Database();
$conn = $db->getConnection();

// Controleer dat het bestand van de ingelogde gebruiker is
$stmt = $conn->prepare("SELECT filename, file_hash FROM file WHERE file_id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$file = $stmt->fetch();

if (!$file) {
    header("Location: index.php?error=toegang");
    exit;
}

$path = __DIR__ . '/server/uploads/' . $file['filename'];

if (!file_exists($path)) {
    header("Location: index.php?error=nietgevonden");
    exit;
}

// ── Integriteitscontrole ──────────────────────────────────────
$currentHash = hash_file('sha256', $path);
if (!hash_equals($file['file_hash'], $currentHash)) {
    header("Location: index.php?error=integriteit");
    exit;
}
// ─────────────────────────────────────────────────────────────

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
readfile($path);
exit;