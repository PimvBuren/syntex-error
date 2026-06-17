<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/server/classes/classDatabase.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Ongeldig verzoek.");
}

$db   = new Database();
$conn = $db->getConnection();

// Controleer dat het bestand van de ingelogde gebruiker is
$stmt = $conn->prepare("SELECT filename FROM file WHERE file_id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$file = $stmt->fetch();

if (!$file) {
    die("Bestand niet gevonden of geen toegang.");
}

$path = __DIR__ . '/server/uploads/' . $file['filename'];

if (!file_exists($path)) {
    die("Bestand bestaat niet op de server.");
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
readfile($path);
exit;