<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/classDatabase.php';

class FileUpload {

    /**
     * Versleutelt data met AES-256-CBC en een door de gebruiker opgegeven sleutel.
     * Formaat opgeslagen bestand: [16 bytes IV][versleutelde data]
     */
    private function encrypt(string $data, string $userKey): string {
        // Maak een 32-byte sleutel van wat de gebruiker invoert via SHA-256
        $key       = hash('sha256', $userKey, true);
        $iv        = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $iv . $encrypted;
    }

    /**
     * Verwerkt een geüpload bestand:
     * 1. Valideert type en grootte
     * 2. Berekent SHA-256 hash van het originele bestand
     * 3. Versleutelt met de sleutel die de gebruiker zelf opgaf
     * 4. Slaat versleuteld bestand op
     * 5. Slaat metadata op in database
     */
    public function uploadFile(array $file, int $userId, string $userKey = ''): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Upload mislukt (foutcode: ' . $file['error'] . ').';
        }

        $filename  = basename($file['name']);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, UPLOAD_ALLOWED)) {
            return 'Bestandstype niet toegestaan. Alleen: ' . implode(', ', UPLOAD_ALLOWED) . '.';
        }

        if ($file['size'] > UPLOAD_MAX_SIZE) {
            return 'Bestand is te groot. Maximum is ' . (UPLOAD_MAX_SIZE / 1000) . ' KB.';
        }

        if (empty($userKey)) {
            return 'Vul een encryptiesleutel in.';
        }

        $fileData = file_get_contents($file['tmp_name']);
        if ($fileData === false) {
            return 'Bestand kon niet worden gelezen.';
        }

        // Hash van het ORIGINELE bestand
        $fileHash = hash('sha256', $fileData);

        // Versleutel met de gebruikerssleutel
        $encryptedData = $this->encrypt($fileData, $userKey);

        $targetFile = UPLOAD_DIR . $filename;
        if (file_put_contents($targetFile, $encryptedData) === false) {
            return 'Bestand kon niet worden opgeslagen.';
        }

        $database = new Database();
        $conn     = $database->getConnection();

        $stmt = $conn->prepare(
            "INSERT INTO file (user_id, filename, file_type, file_size, file_hash, uploaded_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$userId, $filename, $file['type'], $file['size'], $fileHash]);

        return 'Bestand succesvol geüpload.';
    }
}