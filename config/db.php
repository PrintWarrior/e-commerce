<?php
session_start();

$host = 'localhost';
$dbname = 'e_commerce';
$username = 'root'; // Change as needed
$password = '';     // Change as needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>