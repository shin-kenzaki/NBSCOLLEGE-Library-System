<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch active reservations for the logged-in user where recieved_date is null
$query = "SELECT r.id, b.title, r.reserve_date, r.status 
          FROM reservations r 
          JOIN books b ON r.book_id = b.id 
          WHERE r.user_id = ? AND r.recieved_date IS NULL AND r.status = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

include 'inc/header.php';
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">My Book Reservations</h1>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Reservations</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Reserve Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $row['title']; ?></td>
                                        <td><?php echo $row['reserve_date']; ?></td>
                                        <td><?php echo $row['status'] ? 'Waiting for librarian to ready your book' : 'Inactive'; ?></td>
                                        <td>
                                            <button class="btn btn-danger btn-sm cancel-reservation" data-id="<?php echo $row['id']; ?>" title="Cancel Reservation">
                                                <i class="fas fa-times"></i> <!-- Cancel icon -->
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No reservations found.</td>
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

<script>
$(document).ready(function() {
    $('.cancel-reservation').on('click', function() {
        var reservationId = $(this).data('id');
        if (confirm('Are you sure you want to cancel this reservation?')) {
            $.ajax({
                url: 'cancel_reservation.php',
                type: 'POST',
                data: { reservation_id: reservationId },
                success: function(response) {
                    var res = JSON.parse(response);
                    alert(res.message);
                    if (res.success) {
                        location.reload();
                    }
                },
                error: function() {
                    alert('Failed to cancel reservation.');
                }
            });
        }
    });
});
</script>
