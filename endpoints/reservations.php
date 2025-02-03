<?php
// Check the request method and route accordingly
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    handleGetReservations($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    handleInsertReservation($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    handleUpdateReservation($conn);
} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    handleDeleteReservation($conn);
} else {
    echo json_encode(["message" => "Invalid Request"]);
}

/**
 * Handle GET requests: Retrieve all Reservations
 */
function handleGetReservations($conn)
{
    $sql = "SELECT * FROM reservations";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
        echo json_encode($reservations);
    } else {
        echo json_encode(["message" => "No Reservations found"]);
    }
}

/**
 * Handle POST requests: Insert a single Reservation
 */
function handleInsertReservation($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["error" => "Invalid input"]);
        return;
    }

    $fields = ["user_id", "book_id", "reserve_date", "cancel_date", "recieved_date"];
    $values = [];

    foreach ($fields as $field) {
        $values[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : null;
    }

    $sql = "INSERT INTO reservations (" . implode(", ", array_keys($values)) . ") 
            VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Reservation added"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle PUT requests: Update all fields of a Reservation
 */
function handleUpdateReservation($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $fields = ["id", "user_id", "book_id", "reserve_date", "cancel_date", "recieved_date"];
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
    $sql = "UPDATE reservations SET " . implode(", ", $updates) . " WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Reservation updated"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}

/**
 * Handle DELETE requests: Delete a Reservation by ID
 */
function handleDeleteReservation($conn)
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["error" => "ID is required"]);
        return;
    }

    $id = $conn->real_escape_string($data["id"]);
    $sql = "DELETE FROM reservations WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "Reservation deleted"]);
    } else {
        echo json_encode(["error" => $conn->error]);
    }
}
