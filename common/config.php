<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';        // XAMPP default
$db = 'awdsite_result';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>

