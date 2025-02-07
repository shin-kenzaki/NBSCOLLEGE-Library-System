<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetPubishers($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertPubisher($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    handleUpdatePubisher($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeletePubisher($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all publishers
 */
function handleGetPubishers($conn)
{
    $sql = "SELECT * FROM publishers";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $publishers = [];
        while ($row = $result->fetch_assoc()) {
            $publishers[] = $row;
        }
        echo json_encode($publishers);
    } else {
        echo json_encode(["message" => "No publishers found"]);
    }
}

/**
 * Handle POST requests: Insert a single publisher
 */
function handleInsertPubisher($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["error" => "Invalid input"]);
        return;
    }

    $fields = ["company", "place"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO publishers (" . implode(", ", array_keys($values)) . ") 
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Publisher added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle PUT requests: Update all fields of a publisher
 */
function handleUpdatePubisher($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $fields = ["id", "company", "place"];
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
    $sql = "UPDATE publishers SET " . implode(", ", $updates) . " WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Publisher updated"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle DELETE requests: Delete a publisher by ID
 */
function handleDeletePubisher($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $id = $conn->real_escape_string($data["id"]);
    $sql = "DELETE FROM publishers WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Publisher deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}
