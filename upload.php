<?php
require_once 'classes/classUpload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);



?>
<form action="upload.php" method="post" enctype="multipart/form-data">
  Select image to upload:
  <input type="file" name="fileToUpload" id="fileToUpload">
  <input type="submit" value="Upload Image" name="submit">
</form>
