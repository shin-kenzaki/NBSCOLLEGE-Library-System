<?php
/**
 * Database Connection
 * 
 * This file establishes a connection to the database used by the Library System.
 * It is included in various parts of the application where database access is needed.
 */

// Database credentials
$hostname = "localhost";
$username = "root";
$password = "shinkenzaki032324";
$database = "librarysystem";

// Create connection
$conn = mysqli_connect($hostname, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to ensure proper handling of special characters
mysqli_set_charset($conn, "utf8mb4");
?>
