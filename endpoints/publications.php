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

    $fields = ["books_id", "publishers_id", "publish_date"];
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

    if (!isset($data["books_id"]) || !isset($data["publishers_id"])) {
        echo json_encode(["error" => "Both books_id and publishers_id are required"]);
        return;
    }

    $books_id = $conn->real_escape_string($data["books_id"]);
    $publishers_id = $conn->real_escape_string($data["publishers_id"]);

    $sql = "DELETE FROM publications WHERE books_id = '$books_id' AND publishers_id = '$publishers_id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Publication deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}