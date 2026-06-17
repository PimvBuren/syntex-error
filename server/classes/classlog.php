<?php

class Log {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Sla een actie op in de activity_log tabel.
     */
    public function log(string $action, ?int $userId = null, ?int $fileId = null, string $details = ''): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $stmt = $this->conn->prepare(
            "INSERT INTO activity_log (user_id, action, file_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $action, $fileId, $details, $ip]);
    }

    /**
     * Haal alle logs op.
     */
    public function getAll(int $limit = 100): array {
        $stmt = $this->conn->prepare(
            "SELECT l.*, u.username
             FROM activity_log l
             LEFT JOIN user u ON u.user_id = l.user_id
             ORDER BY l.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Haal logs op van één of meerdere actietypes.
     * Bijvoorbeeld: ['login', 'login_failed'] voor alleen loginactiviteit.
     */
    public function getByAction(array $actions, int $limit = 100): array {
        $placeholders = implode(',', array_fill(0, count($actions), '?'));
        $stmt = $this->conn->prepare(
            "SELECT l.*, u.username
             FROM activity_log l
             LEFT JOIN user u ON u.user_id = l.user_id
             WHERE l.action IN ($placeholders)
             ORDER BY l.created_at DESC
             LIMIT ?"
        );

        $params = $actions;
        $params[] = $limit;

        // Bind limit als integer apart
        foreach ($actions as $i => $action) {
            $stmt->bindValue($i + 1, $action, PDO::PARAM_STR);
        }
        $stmt->bindValue(count($actions) + 1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Haal logs op van één specifieke gebruiker.
     */
    public function getByUser(int $userId, int $limit = 50): array {
        $stmt = $this->conn->prepare(
            "SELECT * FROM activity_log
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}