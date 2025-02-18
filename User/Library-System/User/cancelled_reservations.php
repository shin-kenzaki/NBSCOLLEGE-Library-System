<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch reservation history for the logged-in user
$query = "SELECT r.id, b.title, r.reserve_date, r.cancel_date, r.recieved_date, r.status 
          FROM reservations r 
          JOIN books b ON r.book_id = b.id 
          WHERE r.user_id = ? AND (r.cancel_date IS NOT NULL OR r.recieved_date IS NOT NULL)";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

include 'inc/header.php';
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">My Reservation History</h1>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Reservation History</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Reserve Date</th>
                                <th>Cancel Date</th>
                                <th>Received Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $row['title']; ?></td>
                                        <td><?php echo $row['reserve_date']; ?></td>
                                        <td><?php echo $row['cancel_date'] ? $row['cancel_date'] : 'N/A'; ?></td>
                                        <td><?php echo $row['recieved_date'] ? $row['recieved_date'] : 'N/A'; ?></td>
                                        <td><?php echo $row['status'] ? 'Active' : 'Inactive'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No reservation history found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>