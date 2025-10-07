<?php
// db_connect.php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';       // change if you have a password
$DB_NAME = 'innodb';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
