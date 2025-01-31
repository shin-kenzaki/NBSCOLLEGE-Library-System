<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetBooks($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertBook($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    handleUpdateBook($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeleteBook($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all books
 */
function handleGetBooks($conn)
{
    $sql = "SELECT * FROM books";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        echo json_encode($books);
    } else {
        echo json_encode(["message" => "No books found"]);
    }
}

/**
 * Handle POST requests: Insert a single book
 */
function handleInsertBook($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data["id"])) {
        echo json_encode(["error" => "Invalid input or missing ID"]);
        return;
    }

    $fields = ["id", "title", "preferred_title", "parallel_title", "front_image", "back_image", "height", "width", "series", "volume", "edition", "total_pages", "ISBN", "content_type", "media_type", "carrier_type", "URL"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO books (" . implode(", ", array_keys($values)) . ")
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Book added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle PUT requests: Update all fields of a book
 */
function handleUpdateBook($conn)
{
    // Get the raw POST data (it will be in JSON format)
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if the data has the necessary 'id' field
    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    // Define the fields to update
    $fields = ["id", "title", "preferred_title", "parallel_title", "front_image", "back_image", "height", "width", "series", "volume", "edition", "total_pages", "ISBN", "content_type", "media_type", "carrier_type", "URL"];

    // Prepare the fields to update
    $updates = [];
    foreach ($fields as $field) {
        if (isset($data[$field])) {
            // Escape values to avoid SQL injection
            $updates[] = "$field = '" . $conn->real_escape_string($data[$field]) . "'";
        } else {
            // If a field is missing in the input, send an error
            echo json_encode(["error" => "Missing field: $field"]);
            return;
        }
    }

    // Get the 'id' of the book to update
    $id = $conn->real_escape_string($data["id"]);

    // Prepare the SQL query to update the book
    $sql = "UPDATE books SET " . implode(", ", $updates) . " WHERE id = '$id'";

    // Execute the query and return appropriate response
    if ($conn->query($sql)) {
        echo json_encode(["message" => "Book updated"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}


/**
 * Handle DELETE requests: Delete a book by ID
 */
function handleDeleteBook($conn)
{
    // Get the raw POST data (it will be in JSON format)
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if the data has the necessary 'id' field
    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    // Get the 'id' of the book to delete
    $id = $conn->real_escape_string($data["id"]);

    // Prepare the SQL query to delete the book
    $sql = "DELETE FROM books WHERE id = '$id'";

    // Execute the query and return appropriate response
    if ($conn->query($sql)) {
        echo json_encode(["message" => "Book deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}
?>
