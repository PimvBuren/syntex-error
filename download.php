<?php
require_once 'classes/classDatabase.php';

$id = $_GET['id'];

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT filename FROM file WHERE file_id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    die("Bestand niet gevonden.");
}

$path = __DIR__ . "/uploads/" . $file['filename'];

if (!file_exists($path)) {
    die("Bestand bestaat niet op server.");
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');

readfile($path);
exit;