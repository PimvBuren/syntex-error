<?php
// Database
define('DB_HOST',    'localhost');
define('DB_NAME',    'syntax_error');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT',    3307);

// Upload
define('UPLOAD_MAX_SIZE', 500000);                       // 500 KB
define('UPLOAD_ALLOWED',  ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_DIR',      __DIR__ . '/../server/uploads/');

// Beveiliging
// AES-256 sleutel: precies 32 tekens, nooit aanpassen nadat bestanden zijn opgeslagen
// want dan kunnen bestaande bestanden niet meer ontsleuteld worden.
// Zet dit bestand in .gitignore zodat de sleutel nooit op GitHub komt!
define('AES_KEY', '95ebe91c3b544242662a999d2e30826b');