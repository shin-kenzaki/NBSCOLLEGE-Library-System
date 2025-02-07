<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetWriters($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertWriter($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    handleUpdateWriter($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeleteWriter($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all writers
 */
function handleGetWriters($conn)
{
    $sql = "SELECT * FROM writers";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $writers = [];
        while ($row = $result->fetch_assoc()) {
            $writers[] = $row;
        }
        echo json_encode($writers);
    } else {
        echo json_encode(["message" => "No writers found"]);
    }
}

/**
 * Handle POST requests: Insert a single writer
 */
function handleInsertWriter($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["error" => "Invalid input"]);
        return;
    }

    $fields = ["firstname", "middle_init", "lastname"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO writers (" . implode(", ", array_keys($values)) . ") 
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Writer added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle PUT requests: Update all fields of a writer
 */
function handleUpdateWriter($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $fields = ["id", "firstname", "middle_init", "lastname"];
    $updates = [];
    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = '" . $conn->real_escape_string($data[$field]) . "'";
        } else {
            echo json_encode(["error" => "Missing field: $field"]);
            return;
        }
    }

    $id = $conn->real_escape_string($data["id"]);
    $sql = "UPDATE writers SET " . implode(", ", $updates) . " WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Writer updated"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle DELETE requests: Delete a Writer by ID
 */
function handleDeleteWriter($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $id = $conn->real_escape_string($data["id"]);
    $sql = "DELETE FROM writers WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Writer deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}
