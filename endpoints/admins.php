<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetAdmins($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertAdmin($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    handleUpdateAdmin($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeleteAdmin($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all admins
 */
function handleGetAdmins($conn)
{
    $sql = "SELECT * FROM admins";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $admins = [];
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
        echo json_encode($admins);
    } else {
        echo json_encode(["message" => "No admins found"]);
    }
}

/**
 * Handle POST requests: Insert a single admin
 */
function handleInsertAdmin($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data["id"])) {
        echo json_encode(["error" => "Invalid input or missing ID"]);
        return;
    }

    $fields = ["id", "firstname", "middle_init", "lastname", "username", "password", "image", "role", "date_added", "status", "last_update"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO admins (" . implode(", ", array_keys($values)) . ") 
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Admin added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle PUT requests: Update all fields of an admin
 */
function handleUpdateAdmin($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $fields = ["id", "firstname", "middle_init", "lastname", "username", "password", "image", "role", "date_added", "status", "last_update"];
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
    $sql = "UPDATE admins SET " . implode(", ", $updates) . " WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Admin updated"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle DELETE requests: Delete an admin by ID
 */
function handleDeleteAdmin($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $id = $conn->real_escape_string($data["id"]);
    $sql = "DELETE FROM admins WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Admin deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

