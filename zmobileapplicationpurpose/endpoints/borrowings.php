<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetBorrowings($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertBorrowing($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    handleUpdateBorrowing($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeleteBorrowing($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all Borrowings
 */
function handleGetBorrowings($conn)
{
    $sql = "SELECT * FROM borrowings";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $borrowings = [];
        while ($row = $result->fetch_assoc()) {
            $borrowings[] = $row;
        }
        echo json_encode($borrowings);
    } else {
        echo json_encode(["message" => "No Borrowings found"]);
    }
}

/**
 * Handle POST requests: Insert a single Borrowing
 */
function handleInsertBorrowing($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["error" => "Invalid input"]);
        return;
    }

    $fields = ["user_id", "book_id", "status", "borrow_date", "allowed_days", "due_date", "return_date"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO borrowings (" . implode(", ", array_keys($values)) . ") 
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Borrowing added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle PUT requests: Update all fields of a Borrowing
 */
function handleUpdateBorrowing($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $fields = ["id", "user_id", "book_id", "status", "borrow_date", "allowed_days", "due_date", "return_date"];
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
    $sql = "UPDATE borrowings SET " . implode(", ", $updates) . " WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Borrowing updated"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle DELETE requests: Delete a Borrowing by ID
 */
function handleDeleteBorrowing($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $id = $conn->real_escape_string($data["id"]);
    $sql = "DELETE FROM borrowings WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Borrowing deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}
