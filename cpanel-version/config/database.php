<?php
// Database configuration for cPanel hosting
$servername = "localhost"; // cPanel MySQL hostname
$username = "your_cpanel_username"; // cPanel database username
$password = "your_cpanel_password"; // cPanel database password
$dbname = "your_cpanel_dbname"; // cPanel database name

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>