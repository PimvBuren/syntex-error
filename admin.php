<?php
session_start();

// Beveiliging laag 1: moet ingelogd zijn
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Beveiliging laag 2: moet beheerder zijn (role_id = 2)
if ($_SESSION['role_id'] != 2) {
    header("Location: index.php");
    exit;
}

// Beveiliging laag 3: admin sessie verlopen na 15 minuten inactiviteit
if (isset($_SESSION['admin_last_active']) && time() - $_SESSION['admin_last_active'] > 900) {
    unset($_SESSION['admin_verified']);
}
$_SESSION['admin_last_active'] = time();

// Beveiliging laag 4: extra wachtwoordbevestiging voor de beheerderspagina
$adminError = '';
if (!isset($_SESSION['admin_verified'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_confirm_password'])) {
        require_once __DIR__ . '/server/classes/classDatabase.php';
        require_once __DIR__ . '/server/classes/classUser.php';

        $db   = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT password FROM user WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();

        if ($row && password_verify($_POST['admin_confirm_password'], $row['password'])) {
            $_SESSION['admin_verified'] = true;
        } else {
            $adminError = "Wachtwoord klopt niet.";
        }
    }

    if (!isset($_SESSION['admin_verified'])) {
        ?>
        <!DOCTYPE html>
        <html lang="nl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Beheer – Bevestig toegang</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="stylesheet.css">
        </head>
        <body>
        <div class="auth-wrapper">
            <div class="auth-box">
                <div class="logo">// syntax-error / beheer</div>
                <h1>Toegang bevestigen</h1>
                <p class="file-meta" style="margin-bottom:24px;">
                    Voer je wachtwoord in om de beheerderspagina te openen.
                    Je sessie verloopt na 15 minuten inactiviteit.
                </p>

                <?php if ($adminError): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($adminError) ?></div>
                <?php endif; ?>

                <form method="post" action="admin.php">
                    <div class="form-group">
                        <label for="admin_confirm_password">Wachtwoord</label>
                        <input type="password" id="admin_confirm_password"
                               name="admin_confirm_password" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary form-submit">Bevestigen</button>
                </form>
                <p class="switch-mode" style="margin-top:16px;">
                    <a href="index.php">Terug naar dashboard</a>
                </p>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit;
    }
}

require_once __DIR__ . '/server/classes/classDatabase.php';
require_once __DIR__ . '/server/classes/classLog.php';

$db     = new Database();
$conn   = $db->getConnection();
$logger = new Log($conn);

// Actieve tab
$tab = $_GET['tab'] ?? 'overview';

// Data ophalen
$stmt = $conn->query("SELECT * FROM user ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$stmt = $conn->query("SELECT COUNT(*) AS total_files, SUM(file_size) AS total_size FROM file");
$fileStats = $stmt->fetch();

$stmt = $conn->query("SELECT COUNT(*) AS total_shares FROM file_share");
$shareStats = $stmt->fetch();

// Bestanden met eigenaar
$stmt = $conn->query(
    "SELECT f.*, u.username AS owner_name
     FROM file f
     JOIN user u ON u.user_id = f.user_id
     ORDER BY f.uploaded_at DESC"
);
$allFiles = $stmt->fetchAll();

// Logs per categorie
$loginLogs    = $logger->getByAction(['login', 'login_failed', 'logout', 'register'], 100);
$fileLogs     = $logger->getByAction(['upload', 'upload_failed', 'download', 'download_denied', 'download_failed'], 100);
$securityLogs = $logger->getByAction(['integrity_failed', 'decrypt_failed', 'download_denied'], 100);
$allLogs      = $logger->getAll(200);

$actionLabels = [
    'login'            => ['label' => 'Ingelogd',           'color' => 'var(--green)'],
    'login_failed'     => ['label' => 'Login mislukt',      'color' => 'var(--red)'],
    'logout'           => ['label' => 'Uitgelogd',          'color' => 'var(--muted)'],
    'register'         => ['label' => 'Geregistreerd',      'color' => 'var(--accent)'],
    'upload'           => ['label' => 'Upload',             'color' => 'var(--green)'],
    'upload_failed'    => ['label' => 'Upload mislukt',     'color' => 'var(--red)'],
    'download'         => ['label' => 'Download',           'color' => 'var(--accent)'],
    'download_denied'  => ['label' => 'Toegang geweigerd',  'color' => 'var(--red)'],
    'download_failed'  => ['label' => 'Download mislukt',   'color' => 'var(--red)'],
    'integrity_failed' => ['label' => 'Integriteitsfout',   'color' => 'var(--red)'],
    'decrypt_failed'   => ['label' => 'Verkeerde sleutel',  'color' => 'var(--red)'],
    'share'            => ['label' => 'Gedeeld',            'color' => 'var(--accent)'],
];

function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1)    . ' KB';
    return $bytes . ' B';
}

function logTable(array $logs, array $actionLabels): void {
    if (empty($logs)) {
        echo '<div class="empty-state"><p>Geen activiteit gevonden.</p></div>';
        return;
    }
    echo '<table class="file-table"><thead><tr>
        <th>Tijdstip</th><th>Gebruiker</th><th>Actie</th><th>Details</th><th>IP-adres</th>
    </tr></thead><tbody>';
    foreach ($logs as $log) {
        $info = $actionLabels[$log['action']] ?? ['label' => $log['action'], 'color' => 'var(--muted)'];
        echo '<tr>';
        echo '<td class="file-meta">' . htmlspecialchars($log['created_at']) . '</td>';
        echo '<td><span class="file-name">' . htmlspecialchars($log['username'] ?? '—') . '</span></td>';
        echo '<td><span class="action-badge" style="color:' . $info['color'] . '">' . $info['label'] . '</span></td>';
        echo '<td class="file-meta">' . htmlspecialchars($log['details'] ?? '—') . '</td>';
        echo '<td class="file-meta">' . htmlspecialchars($log['ip_address'] ?? '—') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syntax Error – Beheer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 24px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--accent);
            font-family: var(--font-mono);
        }
        .stat-label {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-top: 4px;
        }
        .action-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            background: rgba(255,255,255,0.06);
            font-family: var(--font-mono);
        }
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0;
        }
        .tab {
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: color 0.15s, border-color 0.15s;
        }
        .tab:hover { color: var(--text); text-decoration: none; }
        .tab.active { color: var(--accent); border-bottom-color: var(--accent); }
        .security-warning {
            background: #2e1a0d;
            border: 1px solid #7a3a10;
            color: #f7a26f;
            padding: 12px 16px;
            border-radius: var(--radius);
            font-size: 13px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>

<nav>
    <span class="nav-brand">// syntax-error / beheer</span>
    <div class="nav-right">
        <span><?= htmlspecialchars($_SESSION['username']) ?> (beheerder)</span>
        <a href="index.php">Dashboard</a>
        <a href="logout.php">Uitloggen</a>
    </div>
</nav>

<div class="page">

    <!-- Statistieken -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= count($users) ?></div>
            <div class="stat-label">Gebruikers</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $fileStats['total_files'] ?? 0 ?></div>
            <div class="stat-label">Bestanden</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= formatBytes((int)($fileStats['total_size'] ?? 0)) ?></div>
            <div class="stat-label">Totale opslag</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $shareStats['total_shares'] ?? 0 ?></div>
            <div class="stat-label">Gedeeld</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count($securityLogs) ?></div>
            <div class="stat-label">Beveiligingsincidenten</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <a href="admin.php?tab=overview"  class="tab <?= $tab === 'overview'  ? 'active' : '' ?>">Overzicht</a>
        <a href="admin.php?tab=users"     class="tab <?= $tab === 'users'     ? 'active' : '' ?>">Gebruikers</a>
        <a href="admin.php?tab=files"     class="tab <?= $tab === 'files'     ? 'active' : '' ?>">Bestanden</a>
        <a href="admin.php?tab=logins"    class="tab <?= $tab === 'logins'    ? 'active' : '' ?>">Logins</a>
        <a href="admin.php?tab=filelogs"  class="tab <?= $tab === 'filelogs'  ? 'active' : '' ?>">Bestandsactiviteit</a>
        <a href="admin.php?tab=security"  class="tab <?= $tab === 'security'  ? 'active' : '' ?>">Beveiligingsincidenten</a>
        <a href="admin.php?tab=all"       class="tab <?= $tab === 'all'       ? 'active' : '' ?>">Alle logs</a>
    </div>

    <?php if ($tab === 'overview'): ?>
    <!-- Overzicht: laatste activiteit per categorie -->
    <div class="card">
        <h2>Recente logins</h2>
        <?php logTable($loginLogs, $actionLabels); ?>
    </div>
    <div class="card">
        <h2>Recente bestandsactiviteit</h2>
        <?php logTable($fileLogs, $actionLabels); ?>
    </div>
    <?php if (!empty($securityLogs)): ?>
    <div class="card">
        <div class="security-warning">
            ⚠ Er zijn <?= count($securityLogs) ?> beveiligingsincident(en) gedetecteerd.
            <a href="admin.php?tab=security" style="color:inherit; font-weight:600;">Bekijk details →</a>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif ($tab === 'users'): ?>
    <div class="card">
        <h2>Alle gebruikers</h2>
        <table class="file-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Gebruikersnaam</th>
                    <th>E-mail</th>
                    <th>Rol</th>
                    <th>Aangemaakt op</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="file-meta"><?= $user['user_id'] ?></td>
                    <td><span class="file-name"><?= htmlspecialchars($user['username']) ?></span></td>
                    <td class="file-meta"><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="action-badge" style="color:<?= $user['role_id'] == 2 ? 'var(--accent)' : 'var(--muted)' ?>">
                            <?= $user['role_id'] == 2 ? 'Beheerder' : 'Gebruiker' ?>
                        </span>
                    </td>
                    <td class="file-meta"><?= htmlspecialchars($user['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($tab === 'files'): ?>
    <div class="card">
        <h2>Alle bestanden</h2>
        <table class="file-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Bestandsnaam</th>
                    <th>Eigenaar</th>
                    <th>Grootte</th>
                    <th>SHA-256 hash</th>
                    <th>Geüpload op</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allFiles as $file): ?>
                <tr>
                    <td class="file-meta"><?= $file['file_id'] ?></td>
                    <td><span class="file-name"><?= htmlspecialchars($file['filename']) ?></span></td>
                    <td class="file-meta"><?= htmlspecialchars($file['owner_name']) ?></td>
                    <td class="file-meta"><?= formatBytes((int)$file['file_size']) ?></td>
                    <td class="file-meta" title="<?= htmlspecialchars($file['file_hash']) ?>"
                        style="font-family:var(--font-mono); font-size:11px; cursor:help;">
                        <?= substr($file['file_hash'], 0, 16) ?>…
                    </td>
                    <td class="file-meta"><?= htmlspecialchars($file['uploaded_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($tab === 'logins'): ?>
    <div class="card">
        <h2>Login- en registratieactiviteit</h2>
        <?php logTable($loginLogs, $actionLabels); ?>
    </div>

    <?php elseif ($tab === 'filelogs'): ?>
    <div class="card">
        <h2>Upload- en downloadactiviteit</h2>
        <?php logTable($fileLogs, $actionLabels); ?>
    </div>

    <?php elseif ($tab === 'security'): ?>
    <div class="card">
        <h2>Beveiligingsincidenten</h2>
        <?php if (!empty($securityLogs)): ?>
            <div class="security-warning" style="margin-bottom:16px;">
                Hieronder staan mislukte ontsleutelingen, integriteitsproblemen en geweigerde downloads.
                Controleer of dit aanvallen zijn of gewoon verkeerde sleutels.
            </div>
        <?php endif; ?>
        <?php logTable($securityLogs, $actionLabels); ?>
    </div>

    <?php elseif ($tab === 'all'): ?>
    <div class="card">
        <h2>Alle activiteit (laatste 200)</h2>
        <?php logTable($allLogs, $actionLabels); ?>
    </div>
    <?php endif; ?>

</div>
</body>
</html>