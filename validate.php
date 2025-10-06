<?php

session_start();

include 'db.php';

header('Content-Type: application/json');

$regno = $_POST['regno'];
$password = $_POST['password'];


$sql= "SELECT * FROM users WHERE regno=? and password=?";
$stmt=$conn->prepare($sql);
$stmt->bind_param("ss", $regno,$password);

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['username']=$regno;
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error"]);
}

?>