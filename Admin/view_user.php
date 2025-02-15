<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';
include '../inc/status_helper.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user details
$stmt = $conn->prepare("
    SELECT u.*, 
    COUNT(CASE WHEN b.status = 'borrowed' THEN 1 END) as active_borrowings,
    COUNT(CASE WHEN b.status = 'returned' THEN 1 END) as total_returned,
    COUNT(CASE WHEN b.status IN ('damaged', 'lost') THEN 1 END) as incidents
    FROM users u 
    LEFT JOIN borrowings b ON u.id = b.user_id 
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "<script>
        alert('User not found');
        window.location.href='users_list.php';
    </script>";
    exit;
}

list($status_class, $status_text) = getStatusDisplay($user['status']);
?>

<div id="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">User Profile</h1>
            <a href="users_list.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="row">
            <div class="col-xl-4">
                <!-- Profile picture card -->
                <div class="card mb-4 mb-xl-0">
                    <div class="card-header">Profile Picture</div>
                    <div class="card-body text-center">
                        <img class="img-account-profile rounded-circle mb-2" 
                             src="<?= htmlspecialchars($user['image'] ?? '../assets/img/default-avatar.png') ?>" 
                             alt="User profile picture"
                             style="width: 180px; height: 180px; object-fit: cover;">
                    </div>
                </div>

                <!-- Statistics card -->
                <div class="card mt-4">
                    <div class="card-header">Borrowing Statistics</div>
                    <div class="card-body">
                        <div class="small mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Active Borrowings:</span>
                                <span class="badge badge-info"><?= $user['active_borrowings'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Returned:</span>
                                <span class="badge badge-success"><?= $user['total_returned'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Incidents:</span>
                                <span class="badge badge-warning"><?= $user['incidents'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <!-- Account details card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>Account Details</div>
                        <a href="edit_user.php?id=<?= $user_id ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- User details here -->
                        <div class="row">
                            <div class="col-sm-4 mb-3">
                                <label class="small text-muted mb-1">School ID</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($user['school_id']) ?></div>
                            </div>
                            <div class="col-sm-4 mb-3">
                                <label class="small text-muted mb-1">Status</label>
                                <div><span class="badge <?= $status_class ?>"><?= $status_text ?></span></div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-sm-4 mb-3">
                                <label class="small text-muted mb-1">First Name</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($user['firstname']) ?></div>
                            </div>
                            <div class="col-sm-4 mb-3">
                                <label class="small text-muted mb-1">Middle Initial</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($user['middle_init'] ?? 'N/A') ?></div>
                            </div>
                            <div class="col-sm-4 mb-3">
                                <label class="small text-muted mb-1">Last Name</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($user['lastname']) ?></div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label class="small text-muted mb-1">Email Address</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($user['email']) ?></div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="small text-muted mb-1">Contact Number</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($user['contact_number'] ?? 'N/A') ?></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label class="small text-muted mb-1">Date Added</label>
                                <div class="h5 mb-0"><?= date('F d, Y', strtotime($user['date_added'])) ?></div>
                            </div>
                            <?php if ($user['last_update']): ?>
                            <div class="col-sm-6 mb-3">
                                <label class="small text-muted mb-1">Last Updated</label>
                                <div class="h5 mb-0"><?= date('F d, Y g:i A', strtotime($user['last_update'])) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Borrowing History -->
                <div class="card">
                    <div class="card-header">Recent Borrowings</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Book</th>
                                        <th>Borrowed Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $borrowings = $conn->prepare("
                                        SELECT b.*, bk.title as book_title 
                                        FROM borrowings b 
                                        JOIN books bk ON b.book_id = bk.id 
                                        WHERE b.user_id = ? 
                                        ORDER BY b.borrow_date DESC 
                                        LIMIT 5
                                    ");
                                    $borrowings->bind_param("i", $user_id);
                                    $borrowings->execute();
                                    $result = $borrowings->get_result();
                                    
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['book_title']) . "</td>";
                                        echo "<td>" . date('M d, Y', strtotime($row['borrow_date'])) . "</td>";
                                        echo "<td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>";
                                        echo "<td><span class='badge badge-" . 
                                            ($row['status'] == 'borrowed' ? 'info' : 
                                            ($row['status'] == 'returned' ? 'success' : 
                                            ($row['status'] == 'damaged' ? 'warning' : 'danger'))) . 
                                            "'>" . ucfirst($row['status']) . "</span></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../Admin/inc/footer.php'; ?>
