<?php
require_once __DIR__ . '/classDatabase.php';

class Share {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Deel een bestand met een andere gebruiker via gebruikersnaam.
     * Controleert: bestand bestaat, eigenaar klopt, gebruiker bestaat, niet al gedeeld.
     */
    public function shareFile(int $fileId, int $ownerId, string $targetUsername): string {
        // Controleer of het bestand van deze eigenaar is
        $stmt = $this->conn->prepare("SELECT file_id FROM file WHERE file_id = ? AND user_id = ?");
        $stmt->execute([$fileId, $ownerId]);
        if (!$stmt->fetch()) {
            return "Bestand niet gevonden of geen toegang.";
        }

        // Zoek de gebruiker op waarme gedeeld wordt
        $stmt = $this->conn->prepare("SELECT user_id FROM user WHERE username = ?");
        $stmt->execute([$targetUsername]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            return "Gebruiker '$targetUsername' bestaat niet.";
        }

        // Niet met jezelf delen
        if ($targetUser['user_id'] === $ownerId) {
            return "Je kunt een bestand niet met jezelf delen.";
        }

        // Controleer of het al gedeeld is
        $stmt = $this->conn->prepare(
            "SELECT share_id FROM file_share WHERE file_id = ? AND shared_with = ?"
        );
        $stmt->execute([$fileId, $targetUser['user_id']]);
        if ($stmt->fetch()) {
            return "Dit bestand is al gedeeld met '$targetUsername'.";
        }

        // Deel het bestand
        $stmt = $this->conn->prepare(
            "INSERT INTO file_share (file_id, owner_id, shared_with) VALUES (?, ?, ?)"
        );
        $stmt->execute([$fileId, $ownerId, $targetUser['user_id']]);

        return "Bestand gedeeld met '$targetUsername'.";
    }

    /**
     * Verwijder een gedeeld bestand (alleen eigenaar mag dit).
     */
    public function unshareFile(int $fileId, int $ownerId, int $sharedWithId): string {
        $stmt = $this->conn->prepare(
            "DELETE FROM file_share WHERE file_id = ? AND owner_id = ? AND shared_with = ?"
        );
        $stmt->execute([$fileId, $ownerId, $sharedWithId]);
        return "Toegang ingetrokken.";
    }

    /**
     * Haal op met wie een bestand gedeeld is (voor de eigenaar).
     */
    public function getSharedWith(int $fileId, int $ownerId): array {
        $stmt = $this->conn->prepare(
            "SELECT u.user_id, u.username, fs.shared_at
             FROM file_share fs
             JOIN user u ON u.user_id = fs.shared_with
             WHERE fs.file_id = ? AND fs.owner_id = ?"
        );
        $stmt->execute([$fileId, $ownerId]);
        return $stmt->fetchAll();
    }

    /**
     * Haal bestanden op die met deze gebruiker gedeeld zijn.
     */
    public function getFilesSharedWithMe(int $userId): array {
        $stmt = $this->conn->prepare(
            "SELECT f.*, u.username AS owner_name
             FROM file_share fs
             JOIN file f ON f.file_id = fs.file_id
             JOIN user u ON u.user_id = fs.owner_id
             WHERE fs.shared_with = ?
             ORDER BY fs.shared_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Controleer of een gebruiker toegang heeft tot een bestand
     * (eigenaar of gedeeld met hem).
     */
    public function hasAccess(int $fileId, int $userId): bool {
        // Eigenaar check
        $stmt = $this->conn->prepare("SELECT file_id FROM file WHERE file_id = ? AND user_id = ?");
        $stmt->execute([$fileId, $userId]);
        if ($stmt->fetch()) return true;

        // Gedeeld met check
        $stmt = $this->conn->prepare(
            "SELECT share_id FROM file_share WHERE file_id = ? AND shared_with = ?"
        );
        $stmt->execute([$fileId, $userId]);
        return (bool)$stmt->fetch();
    }
}