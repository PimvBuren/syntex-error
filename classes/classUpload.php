<?php

class FileUpload
{
    public function uploadFile(array $file): string
    {
        $targetDir = __DIR__ . '/../uploads/';
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
            return 'File uploaded successfully.';
        }

        return 'Could not save uploaded file.';
    }
}
