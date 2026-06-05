<?php
// db_connect.php

date_default_timezone_set('Asia/Kuala_Lumpur');

$host = '';
$port = '';
$dbname = 'fkstudentclub&eventmanagementsystem'; // Change this to your exact database name
$username = 'root';                              // Default XAMPP username
$password = '';                                  // Default XAMPP password is empty

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Set PDO error mode to exception for easier debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch data as associative arrays by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Match PHP timezone so NOW() and datetime columns stay consistent
    $pdo->exec("SET time_zone = '+08:00'");

} catch (PDOException $e) {
    // If connection fails, stop execution and show an error
    die("Database connection failed: " . $e->getMessage());
}
?>