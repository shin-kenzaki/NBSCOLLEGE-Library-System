<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';
include 'inc/status_helper.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$errors = [];
$user = null;

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('User not found');
        window.location.href='users_list.php';
    </script>";
    exit;
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = $_POST['school_id'];
    $firstname = trim($_POST['firstname']);
    $middle_init = trim($_POST['middle_init']) ?? NULL;
    $lastname = trim($_POST['lastname']);
    $email = $_POST['email'];
    $contact_no = $_POST['contact_no']; // Changed from contact_number
    $usertype = $_POST['usertype'];
    $status = $_POST['status'];
    
    // Validation
    if (empty($school_id)) $errors['school_id'] = "School ID is required.";
    if (empty($firstname)) $errors['firstname'] = "First name is required.";
    if (empty($lastname)) $errors['lastname'] = "Last name is required.";
    if (empty($email)) $errors['email'] = "Email is required.";

    // Check for duplicates (excluding current user)
    if (!$errors) {
        $stmt_check = $conn->prepare("SELECT school_id, email FROM users WHERE (school_id = ? OR email = ?) AND id != ?");
        $stmt_check->bind_param("ssi", $school_id, $email, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            while ($row = $result_check->fetch_assoc()) {
                if ($row['school_id'] == $school_id) $errors['school_id'] = "This School ID is already in use.";
                if ($row['email'] == $email) $errors['email'] = "This email is already taken.";
            }
        }
    }

    // If no errors, update the user
    if (!$errors) {
        $update_sql = "UPDATE users SET 
            school_id = ?,
            firstname = ?,
            middle_init = ?,
            lastname = ?,
            email = ?,
            contact_no = ?,
            usertype = ?, 
            status = ?,
            last_update = NOW()
            WHERE id = ?";
            
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("sssssssii", $school_id, $firstname, $middle_init, $lastname, $email, $contact_no, $usertype, $status, $user_id);

        if ($stmt_update->execute()) {
            echo "<script>
                alert('User updated successfully');
                window.location.href='users_list.php';
            </script>";
            exit;
        } else {
            $errors['general'] = "Error updating user: " . $conn->error;
        }
    }
}
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit User</h6>
            </div>
            <div class="card-body">
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger"><?= $errors['general'] ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>School ID</label>
                        <input type="text" name="school_id" class="form-control" value="<?= htmlspecialchars($user['school_id']) ?>">
                        <?php if (isset($errors['school_id'])): ?>
                            <small class="text-danger"><?= $errors['school_id'] ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($user['firstname']) ?>">
                        <?php if (isset($errors['firstname'])): ?>
                            <small class="text-danger"><?= $errors['firstname'] ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" name="middle_init" class="form-control" value="<?= htmlspecialchars($user['middle_init'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($user['lastname']) ?>">
                        <?php if (isset($errors['lastname'])): ?>
                            <small class="text-danger"><?= $errors['lastname'] ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
                        <?php if (isset($errors['email'])): ?>
                            <small class="text-danger"><?= $errors['email'] ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_no" class="form-control" value="<?= htmlspecialchars($user['contact_no'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>User Type</label>
                        <select name="usertype" class="form-control">
                            <option value="" disabled>Select User Type</option>
                            <option value="student" <?= $user['usertype'] == 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="faculty" <?= $user['usertype'] == 'faculty' ? 'selected' : '' ?>>Faculty</option>
                            <option value="staff" <?= $user['usertype'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="visitor" <?= $user['usertype'] == 'visitor' ? 'selected' : '' ?>>Visitor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="0" <?= $user['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                            <option value="1" <?= $user['status'] == 1 ? 'selected' : '' ?>>Active</option>
                            <option value="2" <?= $user['status'] == 2 ? 'selected' : '' ?>>Banned</option>
                            <option value="3" <?= $user['status'] == 3 ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </div>

                    <div class="form-group text-center mt-4">
                        <a href="users_list.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../Admin/inc/footer.php'; ?>
