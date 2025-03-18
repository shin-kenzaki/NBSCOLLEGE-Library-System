<?php
session_start();

if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';
include 'inc/status_helper.php';
require 'inc/functions.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user details
$stmt = $conn->prepare("
    SELECT u.*, 
    COUNT(CASE WHEN b.status = 'Active' THEN 1 END) as active_borrowings,
    COUNT(CASE WHEN b.status = 'Returned' THEN 1 END) as total_returned,
    COUNT(CASE WHEN b.status IN ('Damaged', 'Lost') THEN 1 END) as incidents
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
$userImage = displayProfileImage($user['user_image']);
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
                             src="<?= htmlspecialchars($userImage) ?>" 
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
                                <label class="small text-muted mb-1">ID Number</label>
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
            </div>
        </div>

        <!-- Borrowing History -->
        <div class="card">
            <div class="card-header">Borrowed Books</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th class="text-center">Accession No.</th>
                                <th class="text-center">Book Title</th>
                                <th class="text-center">Borrower's Name</th>
                                <th class="text-center">ID Number</th>
                                <th class="text-center">Borrow Date</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Shelf Location</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $borrowings = $conn->prepare("
                                SELECT b.id as borrow_id, b.book_id, b.user_id, b.issue_date, b.due_date, b.status,
                                       bk.title, bk.accession, bk.shelf_location,
                                       CONCAT(u.firstname, ' ', u.lastname) AS borrower,
                                       u.school_id
                                FROM borrowings b
                                JOIN books bk ON b.book_id = bk.id
                                JOIN users u ON b.user_id = u.id
                                WHERE b.user_id = ?
                                ORDER BY b.issue_date DESC
                                LIMIT 10
                            ");
                            $borrowings->bind_param("i", $user_id);
                            $borrowings->execute();
                            $result = $borrowings->get_result();
                            
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='text-center'>" . htmlspecialchars($row['accession']) . "</td>";
                                echo "<td class='text-left'>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td class='text-left'>" . htmlspecialchars($row['borrower']) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($row['school_id']) . "</td>";
                                echo "<td class='text-center'>" . date('M d, Y', strtotime($row['issue_date'])) . "</td>";
                                echo "<td class='text-center'>" . date('M d, Y', strtotime($row['due_date'])) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($row['shelf_location']) . "</td>";
                                echo "<td class='text-center'><span class='badge badge-" . 
                                    ($row['status'] == 'Returned' ? 'success' : 
                                    ($row['status'] == 'Damaged' ? 'warning' : 
                                    ($row['status'] == 'Lost' ? 'danger' : 'info'))) . 
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

<?php include '../Admin/inc/footer.php'; ?>
