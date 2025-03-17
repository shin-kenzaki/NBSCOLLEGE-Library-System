<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_ids'])) {
    $admin_ids = $_POST['admin_ids'];
    $admin_ids_string = implode(',', array_map('intval', $admin_ids));

    // Retrieve admin info for updates log
    $sql_select = "SELECT id, firstname, middle_init, lastname, role FROM admins WHERE id IN ($admin_ids_string)";
    $result_select = $conn->query($sql_select);

    if ($result_select->num_rows > 0) {
        $updates_sql = "";
        while($row = $result_select->fetch_assoc()) {
            $admin_id = $_SESSION['admin_employee_id'];
            $admin_role = $_SESSION['role'];
            $admin_fullname = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
            $deactivated_admin_fullname = $row['firstname'] . ' ' . ($row['middle_init'] ? $row['middle_init'] . ' ' : '') . $row['lastname'];
            $deactivated_admin_role = $row['role'];

            $update_title = "$admin_role $admin_fullname Deactivated an Admin";
            $update_message = "$admin_role $admin_fullname Deactivated $deactivated_admin_role $deactivated_admin_fullname";

            // Prepare the SQL query for each admin
            $updates_sql .= "INSERT INTO updates (user_id, role, title, message, `update`) VALUES ('$admin_id', '$admin_role', '$update_title', '$update_message', NOW());";
        }

        // Update the status and insert updates in a single query
        $sql = "UPDATE admins SET status = 0 WHERE id IN ($admin_ids_string);";
        $sql .= $updates_sql;

        if (mysqli_multi_query($conn, $sql)) {
            $response = ['success' => true, 'message' => 'Selected admins deactivated successfully.'];
        } else {
            $response = ['success' => false, 'message' => 'Error updating admins: ' . $conn->error];
        }
    } else {
        $response = ['success' => false, 'message' => 'No admins found with the provided IDs.'];
    }

    echo json_encode($response);
}

$conn->close();
?>
