<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/server/classes/classUpload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $upload  = new FileUpload();
    $message = $upload->uploadFile($_FILES['fileToUpload'], $_SESSION['user_id']);

    $_SESSION['upload_message'] = $message;
    $_SESSION['upload_status']  = ($message === 'Bestand succesvol geüpload.') ? 'success' : 'error';
}

header("Location: index.php");
exit;