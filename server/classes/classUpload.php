<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/classDatabase.php';

class FileUpload {

    /**
     * Verwerkt een geüpload bestand en slaat het op in de database.
     * Geeft een melding terug als string.
     */
    public function uploadFile(array $file, int $userId): string {
        // Controleer op uploadfout
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Upload mislukt (foutcode: ' . $file['error'] . ').';
        }

        $filename      = basename($file['name']);
        $targetFile    = UPLOAD_DIR . $filename;
        $extension     = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Controleer bestandstype
        if (!in_array($extension, UPLOAD_ALLOWED)) {
            return 'Bestandstype niet toegestaan. Alleen: ' . implode(', ', UPLOAD_ALLOWED) . '.';
        }

        // Controleer bestandsgrootte
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            return 'Bestand is te groot. Maximum is ' . (UPLOAD_MAX_SIZE / 1000) . ' KB.';
        }

        // Sla het bestand op
        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            return 'Bestand kon niet worden opgeslagen op de server.';
        }

        // Bereken SHA-256 hash voor integriteitscontrole
        $fileHash = hash_file('sha256', $targetFile);

        // Sla metadata op in de database
        $database = new Database();
        $conn     = $database->getConnection();

        $stmt = $conn->prepare(
            "INSERT INTO file (user_id, filename, file_type, file_size, file_hash, uploaded_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $userId,
            $filename,
            $file['type'],
            $file['size'],
            $fileHash
        ]);

        return 'Bestand succesvol geüpload.';
    }
}