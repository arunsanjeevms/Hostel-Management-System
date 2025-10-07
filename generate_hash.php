<?php
$password = "civil"; // Replace with your desired password
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
?>

