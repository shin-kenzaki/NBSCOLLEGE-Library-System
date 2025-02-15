<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';
include '../inc/status_helper.php';

// Get admin ID from URL
$admin_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$admin_id) {
    echo "<script>
        alert('Invalid admin ID');
        window.location.href='admins_list.php';
    </script>";
    exit;
}

// Initialize variables
$errors = [];
$admin = null;

// Fetch admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('Admin not found');
        window.location.href='admins_list.php';
    </script>";
    exit;
}

$admin = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'];
    $firstname = trim($_POST['firstname']);
    $middle_init = trim($_POST['middle_init']) ?? NULL;
    $lastname = trim($_POST['lastname']);
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    // Validation
    if (empty($employee_id)) $errors['employee_id'] = "Employee ID is required.";
    if (empty($firstname)) $errors['firstname'] = "First name is required.";
    if (empty($lastname)) $errors['lastname'] = "Last name is required.";
    if (empty($email)) $errors['email'] = "Email is required.";
    if (empty($role)) $errors['role'] = "Role is required.";

    // Check for duplicates (excluding current admin)
    if (!$errors) {
        $stmt_check = $conn->prepare("SELECT employee_id, email FROM admins WHERE (employee_id = ? OR email = ?) AND id != ?");
        $stmt_check->bind_param("ssi", $employee_id, $email, $admin_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            while ($row = $result_check->fetch_assoc()) {
                if ($row['employee_id'] == $employee_id) $errors['employee_id'] = "This Employee ID is already in use.";
                if ($row['email'] == $email) $errors['email'] = "This email is already taken.";
            }
        }
    }

    // If no errors, update the admin
    if (!$errors) {
        $update_sql = "UPDATE admins SET 
            employee_id = ?,
            firstname = ?,
            middle_init = ?,
            lastname = ?,
            email = ?,
            role = ?,
            status = ?,
            last_update = NOW()
            WHERE id = ?";
            
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("ssssssii", $employee_id, $firstname, $middle_init, $lastname, $email, $role, $status, $admin_id);

        if ($stmt_update->execute()) {
            echo "<script>
                alert('Admin updated successfully');
                window.location.href='admins_list.php';
            </script>";
            exit;
        } else {
            $errors['general'] = "Error updating admin: " . $conn->error;
        }
    }
}
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit Admin</h6>
            </div>
            <div class="card-body">
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger"><?= $errors['general'] ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" name="employee_id" class="form-control" value="<?= htmlspecialchars($admin['employee_id']) ?>">
                        <?php if (isset($errors['employee_id'])): ?>
                            <small class="text-danger"><?= $errors['employee_id'] ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($admin['firstname']) ?>">
                        <?php if (isset($errors['firstname'])): ?>
                            <small class="text-danger"><?= $errors['firstname'] ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" name="middle_init" class="form-control" value="<?= htmlspecialchars($admin['middle_init'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($admin['lastname']) ?>">
                        <?php if (isset($errors['lastname'])): ?>
                            <small class="text-danger"><?= $errors['lastname'] ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>">
                        <?php if (isset($errors['email'])): ?>
                            <small class="text-danger"><?= $errors['email'] ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="Admin" <?= $admin['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="Librarian" <?= $admin['role'] == 'Librarian' ? 'selected' : '' ?>>Librarian</option>
                            <option value="Encoder" <?= $admin['role'] == 'Encoder' ? 'selected' : '' ?>>Encoder</option>
                        </select>
                        <?php if (isset($errors['role'])): ?>
                            <small class="text-danger"><?= $errors['role'] ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="0" <?= $admin['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                            <option value="1" <?= $admin['status'] == 1 ? 'selected' : '' ?>>Active</option>
                            <option value="2" <?= $admin['status'] == 2 ? 'selected' : '' ?>>Banned</option>
                            <option value="3" <?= $admin['status'] == 3 ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </div>

                    <div class="form-group text-center mt-4">
                        <a href="admins_list.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../Admin/inc/footer.php'; ?>
