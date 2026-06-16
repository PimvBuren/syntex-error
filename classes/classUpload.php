<?php
// laad de database
require_once __DIR__ . '/classDatabase.php';

class FileUpload
{
    // upload functie voor een user in de databse
    public function uploadFile(array $file, int $userId): string
    {
        //upload pad
        $targetDir = __DIR__ . '/../uploads/';

        //haalt alles wat na de laatste / staat uit de file naam
        $filename = basename($file['name']);
        // waar de file opgeslagen moet worden
        $targetFile = $targetDir . $filename;
        // maakt en zet de file extensie om naar lowercase
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // welke soor files mogen
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        // error check voor upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Upload failed.';
        }

        // kert upload af met het verkeerde type
        if (!in_array($imageFileType, $allowedTypes)) {
            return 'file not allowed.';
        }

        // limitteer upload groote
        if ($file['size'] > 500000) {
            return 'file to big';
        }

        // probeer de file naar de upload map te doen
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // als het goed was doet hij het nu naar de database
            $database = new Database();
            $conn = $database->getConnection();

            // soort het voor de db
            $fileType = $file['type'];
            // doet de groote in een variabele zodat het in de database kan
            $fileSize = $file['size'];
            // maakt hash net als wachtwoord
            $fileHash = hash_file('sha256', $targetFile);

            // prepare sql statment om in to file tabel te doen
            $stmt = $conn->prepare(
                "INSERT INTO file 
                (user_id, filename, file_type, file_size, file_hash, uploaded_at)
                VALUES (?, ?, ?, ?, ?, NOW())"
            );

            // excecute de pre statmement plus de variable die daarboven staan
            $stmt->execute([
                $userId,
                $filename,
                $fileType,
                $fileSize,
                $fileHash
            ]);

            return 'File uploaded successfully.';
        }

        // geeror als niet werkt
        return 'Could not save uploaded file.';
    }
}