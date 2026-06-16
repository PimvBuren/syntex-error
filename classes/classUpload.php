<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


class file {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
}

public function uploadFile($file) {
$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

}
}

?>






