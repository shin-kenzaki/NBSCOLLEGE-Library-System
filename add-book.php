<?php
// Include the database connection file
require 'db.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect form data
    $id = $_POST['id'];
    $title = $_POST['title'];
    $preferred_title = $_POST['preferred_title'];
    $parallel_title = $_POST['parallel_title'];
    $front_image = $_POST['front_image'];
    $back_image = $_POST['back_image'];
    $height = $_POST['height'];
    $width = $_POST['width'];
    $series = $_POST['series'];
    $volume = $_POST['volume'];
    $edition = $_POST['edition'];
    $total_pages = $_POST['total_pages'];
    $ISBN = $_POST['ISBN'];
    $content_type = $_POST['content_type'];
    $media_type = $_POST['media_type'];
    $carrier_type = $_POST['carrier_type'];
    $URL = $_POST['URL'];

    // Prepare SQL query
    $sql = "INSERT INTO books (id, title, preferred_title, parallel_title, front_image, back_image, height, width, series, volume, edition, total_pages, ISBN, content_type, media_type, carrier_type, URL) 
            VALUES ('$id', '$title', '$preferred_title', '$parallel_title', '$front_image', '$back_image', '$height', '$width', '$series', '$volume', '$edition', '$total_pages', '$ISBN', '$content_type', '$media_type', '$carrier_type', '$URL')";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        echo "New book added successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book</title>
</head>
<body>
    <h1>Add a New Book</h1>
    <form method="POST" action="">
        <label for="id">ID:</label>
        <input type="text" id="id" name="id" required><br><br>

        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required><br><br>

        <label for="preferred_title">Preferred Title:</label>
        <input type="text" id="preferred_title" name="preferred_title"><br><br>

        <label for="parallel_title">Parallel Title:</label>
        <input type="text" id="parallel_title" name="parallel_title"><br><br>

        <label for="front_image">Front Image URL:</label>
        <input type="text" id="front_image" name="front_image"><br><br>

        <label for="back_image">Back Image URL:</label>
        <input type="text" id="back_image" name="back_image"><br><br>

        <label for="height">Height:</label>
        <input type="text" id="height" name="height"><br><br>

        <label for="width">Width:</label>
        <input type="text" id="width" name="width"><br><br>

        <label for="series">Series:</label>
        <input type="text" id="series" name="series"><br><br>

        <label for="volume">Volume:</label>
        <input type="text" id="volume" name="volume"><br><br>

        <label for="edition">Edition:</label>
        <input type="text" id="edition" name="edition"><br><br>

        <label for="total_pages">Total Pages:</label>
        <input type="text" id="total_pages" name="total_pages"><br><br>

        <label for="ISBN">ISBN:</label>
        <input type="text" id="ISBN" name="ISBN"><br><br>

        <label for="content_type">Content Type:</label>
        <input type="text" id="content_type" name="content_type"><br><br>

        <label for="media_type">Media Type:</label>
        <input type="text" id="media_type" name="media_type"><br><br>

        <label for="carrier_type">Carrier Type:</label>
        <input type="text" id="carrier_type" name="carrier_type"><br><br>

        <label for="URL">URL:</label>
        <input type="text" id="URL" name="URL"><br><br>

        <input type="submit" value="Add Book">
    </form>
</body>
</html>