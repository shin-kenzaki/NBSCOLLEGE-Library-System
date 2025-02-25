<?php
session_start();
include('inc/header.php');

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include('../db.php');

// Get current user's activity log
$user_id = $_SESSION['admin_id'];
$query = "SELECT 
    u.id,
    u.user_id,
    u.role,
    u.status,
    u.update as timestamp,
    CONCAT(a.firstname, ' ', a.lastname) as user_name
FROM updates u
LEFT JOIN admins a ON u.user_id = a.employee_id
WHERE u.user_id = ?
ORDER BY u.update DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Activity Log</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Your Activity History</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="activityTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Activity</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['timestamp'])); ?></td>
                            <td>
                                <?php 
                                $activity = "";
                                if($row['status'] == 'Active login') {
                                    $activity = "Logged into the system";
                                } elseif($row['status'] == 'Inactive Login') {
                                    $activity = "Failed login attempt";
                                } else {
                                    $activity = $row['status'];
                                }
                                echo $activity;
                                ?>
                            </td>
                            <td><?php echo ucfirst($row['role']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo ($row['status'] == 'Active login') ? 'success' : 'danger'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php include('inc/footer.php'); ?>

<script>
$(document).ready(function() {
    $('#activityTable').DataTable({
        "order": [[0, "desc"]], // Sort by date descending
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    });
});
</script>
