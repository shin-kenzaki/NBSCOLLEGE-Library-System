<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetFines($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertFine($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    handleUpdateFine($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeleteFine($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all Fines
 */
function handleGetFines($conn)
{
    $sql = "SELECT * FROM fines";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $Fines = [];
        while ($row = $result->fetch_assoc()) {
            $Fines[] = $row;
        }
        echo json_encode($Fines);
    } else {
        echo json_encode(["message" => "No Fines found"]);
    }
}

/**
 * Handle POST requests: Insert a single Fine
 */
function handleInsertFine($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["error" => "Invalid input"]);
        return;
    }

    $fields = ["borrowing_id", "type", "amount", "status", "date", "payment_date"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO fines (" . implode(", ", array_keys($values)) . ") 
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Fine added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle PUT requests: Update all fields of a Fine
 */
function handleUpdateFine($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $fields = ["id", "borrowing_id", "type", "amount", "status", "date", "payment_date"];
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
    $sql = "UPDATE fines SET " . implode(", ", $updates) . " WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Fine updated"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle DELETE requests: Delete a Fine by ID
 */
function handleDeleteFine($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $id = $conn->real_escape_string($data["id"]);
    $sql = "DELETE FROM fines WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Fine deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}
