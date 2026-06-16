<?php

class FileUpload
{
    public function uploadFile(array $file, int $userId): string
    {
        $targetDir = __DIR__ . '/../uploads/';

        $filename = basename($file['name']);
        $targetFile = $targetDir . $filename;
         $targetFile = $targetDir . $filename;
        $targetFile = $targetDir . basename($file['name']);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Upload failed.';
        }

        if (!in_array($imageFileType, $allowedTypes)) {
            return 'Only JPG, JPEG, PNG and GIF files are allowed.';
        }

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                        $database = new Database();
            $conn = $database->getConnection();

            $fileType = $file['type'];
            $fileSize = $file['size'];
            $fileHash = hash_file('sha256', $targetFile);

            $stmt = $conn->prepare(
                "INSERT INTO files 
                (user_id, filename, file_type, file_size, file_hash, uploaded_at)
                VALUES (?, ?, ?, ?, ?, NOW())"
            );

            $stmt->execute([
                $userId,
                $filename,
                $fileType,
                $fileSize,
                $fileHash
            ]);


            return 'File uploaded successfully.';
        }

        return 'Could not save uploaded file.';
    }
}
