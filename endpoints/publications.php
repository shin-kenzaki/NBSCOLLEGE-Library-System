<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetPublications($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertPublication($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeletePublication($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all publications
 */
function handleGetPublications($conn)
{
    $sql = "SELECT * FROM publications";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $publications = [];
        while ($row = $result->fetch_assoc()) {
            $publications[] = $row;
        }
        echo json_encode($publications);
    } else {
        echo json_encode(["message" => "No publications found"]);
    }
}

/**
 * Handle POST requests: Insert a single publication
 */
function handleInsertPublication($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["error" => "Invalid input"]);
        return;
    }

    $fields = ["book_id", "publisher_id", "publish_date"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO publications (" . implode(", ", array_keys($values)) . ") 
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Publication added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle DELETE requests: Delete a publication by ID
 */
function handleDeletePublication($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["book_id"]) || !isset($data["publisher_id"])) {
        echo json_encode(["error" => "Both book_id and publisher_id are required"]);
        return;
    }

    $book_id = $conn->real_escape_string($data["book_id"]);
    $publisher_id = $conn->real_escape_string($data["publisher_id"]);

    $sql = "DELETE FROM publications WHERE book_id = '$book_id' AND publisher_id = '$publisher_id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Publication deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}