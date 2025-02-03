<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetSchoolUser($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertSchoolUser($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    handleUpdateSchoolUser($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeleteSchoolUser($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all school users
 */
function handleGetSchoolUser($conn)
{
    $sql = "SELECT * FROM school_users";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $school_users = [];
        while ($row = $result->fetch_assoc()) {
            $school_users[] = $row;
        }
        echo json_encode($school_users);
    } else {
        echo json_encode(["message" => "No school users found"]);
    }
}

/**
 * Handle POST requests: Insert a single school user
 */
function handleInsertSchoolUser($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data["id"])) {
        echo json_encode(["error" => "Invalid input or missing ID"]);
        return;
    }

    $fields = ["id", "email", "password", "image", "borrowed_books", "returned_books", "lost_books"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO school_users (" . implode(", ", array_keys($values)) . ") 
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "School user added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle PUT requests: Update all fields of a school user
 */
function handleUpdateSchoolUser($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $fields = ["id", "email", "password", "image", "borrowed_books", "returned_books", "lost_books"];
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
    $sql = "UPDATE school_users SET " . implode(", ", $updates) . " WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "School user updated"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle DELETE requests: Delete a School user by ID
 */
function handleDeleteSchoolUser($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $id = $conn->real_escape_string($data["id"]);
    $sql = "DELETE FROM school_users WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "School user deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}
