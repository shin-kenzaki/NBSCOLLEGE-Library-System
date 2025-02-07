<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetOutsideUser($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertOutsideUser($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    handleUpdateOutsideUser($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeleteOutsideUser($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all outside users
 */
function handleGetOutsideUser($conn)
{
    $sql = "SELECT * FROM outside_users";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $outside_users = [];
        while ($row = $result->fetch_assoc()) {
            $outside_users[] = $row;
        }
        echo json_encode($outside_users);
    } else {
        echo json_encode(["message" => "No outside users found"]);
    }
}

/**
 * Handle POST requests: Insert a single outside user
 */
function handleInsertOutsideUser($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["error" => "Invalid input"]);
        return;
    }

    $fields = ["email", "password", "contact_no", "user_image", "borrowed_books", "returned_books", "damaged_books", "lost_books", "address", "id_type", "id_image", "date_added", "status", "last_update"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO outside_users (" . implode(", ", array_keys($values)) . ") 
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Outside user added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle PUT requests: Update all fields of an outside user
 */
function handleUpdateOutsideUser($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $fields = ["id", "email", "password", "contact_no", "user_image", "borrowed_books", "returned_books", "damaged_books",  "lost_books", "address", "id_type", "id_image", "date_added", "status", "last_update"];
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
    $sql = "UPDATE outside_users SET " . implode(", ", $updates) . " WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Outside user updated"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle DELETE requests: Delete a Outside user by ID
 */
function handleDeleteOutsideUser($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $id = $conn->real_escape_string($data["id"]);
    $sql = "DELETE FROM outside_users WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Outside user deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}
