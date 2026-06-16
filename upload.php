<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'classes/classUpload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $upload = new FileUpload();
    $message = $upload->uploadFile($_FILES['fileToUpload'], $_SESSION['user_id']);
    // Stuur terug naar index na upload
    header("Location: index.php");
    exit;
}

// Als iemand direct naar upload.php gaat zonder formulier, stuur terug
header("Location: index.php");
exit;