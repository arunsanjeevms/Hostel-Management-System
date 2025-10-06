<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mic";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    console.log("DB Connection failed");
}

?>