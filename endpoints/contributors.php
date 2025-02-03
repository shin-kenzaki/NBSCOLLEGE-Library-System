<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetContributors($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertContributor($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeleteContributor($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all contributors
 */
function handleGetContributors($conn)
{
    $sql = "SELECT * FROM contributors";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $contributors = [];
        while ($row = $result->fetch_assoc()) {
            $contributors[] = $row;
        }
        echo json_encode($contributors);
    } else {
        echo json_encode(["message" => "No contributors found"]);
    }
}

/**
 * Handle POST requests: Insert a single contributor
 */
function handleInsertContributor($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["error" => "Invalid input"]);
        return;
    }

    $fields = ["books_id", "writers_id", "role"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO contributors (" . implode(", ", array_keys($values)) . ") 
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Contributor added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle DELETE requests: Delete a contributor by ID
 */
function handleDeleteContributor($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["books_id"]) || !isset($data["writers_id"])) {
        echo json_encode(["error" => "Both books_id and writers_id are required"]);
        return;
    }

    $books_id = $conn->real_escape_string($data["books_id"]);
    $writers_id = $conn->real_escape_string($data["writers_id"]);

    $sql = "DELETE FROM contributors WHERE books_id = '$books_id' AND writers_id = '$writers_id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Contributor deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}