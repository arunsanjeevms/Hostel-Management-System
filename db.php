<?php
<<<<<<< HEAD

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "innodb";

$conn = new mysqli($servername, $username, $password, $dbname);


=======
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mic";

$conn = new mysqli($servername, $username, $password, $dbname);

>>>>>>> 2ae024f59e46468a072166f399153ce69aa00f21
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    console.log("DB Connection failed");
}

?>