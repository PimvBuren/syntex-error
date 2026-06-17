<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/server/classes/classUpload.php';
require_once __DIR__ . '/server/classes/classDatabase.php';
require_once __DIR__ . '/server/classes/classLog.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $userKey = $_POST['encrypt_key'] ?? '';

    $upload  = new FileUpload();
    $message = $upload->uploadFile($_FILES['fileToUpload'], $_SESSION['user_id'], $userKey);

    $db     = new Database();
    $logger = new Log($db->getConnection());

    $success = ($message === 'Bestand succesvol geüpload.');
    $logger->log(
        $success ? 'upload' : 'upload_failed',
        $_SESSION['user_id'],
        null,
        $message . ' — ' . basename($_FILES['fileToUpload']['name'] ?? '')
    );

    $_SESSION['upload_message'] = $message;
    $_SESSION['upload_status']  = $success ? 'success' : 'error';
}

header("Location: index.php");
exit;