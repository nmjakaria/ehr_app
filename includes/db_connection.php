<?php
// includes/db_connection.php

// Database configuration
$host = 'localhost'; 
$dbname = 'ehr_db'; 
$username = 'root';  
$password = '';      

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // echo "Database connection successful!"; // Uncomment for testing connection

} catch (PDOException $e) {
    // If connection fails, display an error message and exit
    die("Database connection failed: " . $e->getMessage());
}
?>