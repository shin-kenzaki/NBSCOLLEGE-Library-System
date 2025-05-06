<?php
$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "librarysystem";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if corporate_contributors table exists and create if it doesn't
$check_corporate_table = mysqli_query($conn, "SHOW TABLES LIKE 'corporate_contributors'");
if (mysqli_num_rows($check_corporate_table) == 0) {
    $create_corporate_table = "CREATE TABLE `corporate_contributors` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `book_id` int(11) NOT NULL,
        `corporate_id` int(11) NOT NULL,
        `role` varchar(50) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `book_id` (`book_id`),
        KEY `corporate_id` (`corporate_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    mysqli_query($conn, $create_corporate_table);
    
    // Check if corporates table exists and create if it doesn't
    $check_corporates_table = mysqli_query($conn, "SHOW TABLES LIKE 'corporates'");
    if (mysqli_num_rows($check_corporates_table) == 0) {
        $create_corporates_table = "CREATE TABLE `corporates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `type` varchar(100) NOT NULL,
            `location` varchar(255) DEFAULT NULL,
            `description` text DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        mysqli_query($conn, $create_corporates_table);
    }
}

return $conn; // ✅ Important: Return the connection object
?>
