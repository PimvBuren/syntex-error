<?php
session_start();

// Sessiecheck - niet ingelogd = terug naar login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/classes/classDatabase.php';

$id = $_GET['id'] ?? null;

// Valideer dat id een getal is
if (!$id || !is_numeric($id)) {
    header("Location: index.php?error=ongeldig");
    exit;
}

$db   = new Database();
$conn = $db->getConnection();

// Haal bestand op - controleer dat het van de ingelogde gebruiker is
$stmt = $conn->prepare(
    "SELECT filename, file_hash FROM file WHERE file_id = ? AND user_id = ?"
);
$stmt->execute([$id, $_SESSION['user_id']]);
$file = $stmt->fetch();

// Bestand niet gevonden of niet van deze gebruiker
if (!$file) {
    header("Location: index.php?error=toegang");
    exit;
}

$path = __DIR__ . "/uploads/" . $file['filename'];

// Controleer of het bestand fysiek bestaat
if (!file_exists($path)) {
    header("Location: index.php?error=nietgevonden");
    exit;
}

// ── Integriteitscontrole ──────────────────────────────────────
// Bereken de hash van het bestand op de server
$currentHash = hash_file('sha256', $path);

// Vergelijk met de opgeslagen hash - hash_equals voorkomt timing attacks
if (!hash_equals($file['file_hash'], $currentHash)) {
    // Hash klopt niet: bestand is aangepast of beschadigd
    header("Location: index.php?error=integriteit");
    exit;
}
// ─────────────────────────────────────────────────────────────

// Alles klopt, stuur het bestand
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
readfile($path);
exit;