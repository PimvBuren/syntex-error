<?php
session_start();

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/server/classes/classDatabase.php';
    require_once __DIR__ . '/server/classes/classLog.php';

    $db     = new Database();
    $logger = new Log($db->getConnection());
    $logger->log('logout', $_SESSION['user_id'], null, "Uitgelogd: " . $_SESSION['username']);
}

session_destroy();
header("Location: login.php");
exit;