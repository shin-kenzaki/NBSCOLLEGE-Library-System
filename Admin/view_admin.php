<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';
include '../inc/status_helper.php';

// Get admin ID from URL
$admin_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// If admin not found
if (!$admin) {
    echo "<script>
        alert('Admin not found');
        window.location.href='admins_list.php';
    </script>";
    exit;
}

list($status_class, $status_text) = getStatusDisplay($admin['status']);
?>

<div id="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Admin Profile</h1>
            <a href="admins_list.php" class="btn btn-secondary btn-sm">
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
                             src="<?= htmlspecialchars($admin['image'] ?? '../assets/img/default-avatar.png') ?>" 
                             alt="Admin profile picture"
                             style="width: 180px; height: 180px; object-fit: cover;">
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <!-- Account details card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>Account Details</div>
                        <a href="edit_admin.php?id=<?= $admin_id ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-4 mb-3">
                                <label class="small text-muted mb-1">Employee ID</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($admin['employee_id']) ?></div>
                            </div>
                            <div class="col-sm-4 mb-3">
                                <label class="small text-muted mb-1">Role</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($admin['role']) ?></div>
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
                                <div class="h5 mb-0"><?= htmlspecialchars($admin['firstname']) ?></div>
                            </div>
                            <div class="col-sm-4 mb-3">
                                <label class="small text-muted mb-1">Middle Initial</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($admin['middle_init'] ?? 'N/A') ?></div>
                            </div>
                            <div class="col-sm-4 mb-3">
                                <label class="small text-muted mb-1">Last Name</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($admin['lastname']) ?></div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label class="small text-muted mb-1">Email Address</label>
                                <div class="h5 mb-0"><?= htmlspecialchars($admin['email']) ?></div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="small text-muted mb-1">Date Added</label>
                                <div class="h5 mb-0"><?= date('F d, Y', strtotime($admin['date_added'])) ?></div>
                            </div>
                        </div>

                        <?php if ($admin['last_update']): ?>
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label class="small text-muted mb-1">Last Updated</label>
                                <div class="h5 mb-0"><?= date('F d, Y g:i A', strtotime($admin['last_update'])) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../Admin/inc/footer.php'; ?>

<script>
$(document).ready(function() {
    // Add any JavaScript functionality here if needed
});
</script>
