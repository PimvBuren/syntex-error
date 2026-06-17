<?php
// ── Databaseconfiguratie ──────────────────────────────────────
// Dit bestand bevat gevoelige gegevens.
// Zet config/ in je .gitignore zodat dit nooit in de repository komt.

define('DB_HOST', 'localhost');
define('DB_NAME', 'syntax_error');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// ── Uploadconfiguratie ────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/../server/uploads/');
define('UPLOAD_MAX_SIZE', 500000);          // 500 KB
define('UPLOAD_ALLOWED', ['jpg', 'jpeg', 'png', 'gif']);