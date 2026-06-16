<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'classes/classDatabase.php';

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT * FROM file");
$files = $stmt->fetchAll();
?>
<html>
<body>

<form action="upload.php" method="post" enctype="multipart/form-data">
  Select image to upload:
  <input type="file" name="fileToUpload" id="fileToUpload">
  <input type="submit" value="Upload Image" name="submit">
</form>
<h2>Jouw uploads</h2>
<?php foreach ($files as $file): ?>
        <!-- FOTO TONEN -->
        <img src="uploads/<?= $file['filename'] ?>" width="150">
        <br>
        <a href="download.php?id=<?= $file['file_id'] ?>">
            Download
        </a>
    </div>
<?php endforeach; ?>

</body>
</html>