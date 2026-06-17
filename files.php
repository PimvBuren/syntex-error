<?php
require_once '/classes/classDatabase.php';

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT * FROM file");
$files = $stmt->fetchAll();
?>