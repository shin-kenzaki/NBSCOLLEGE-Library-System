<?php
session_start();
require('../db.php');

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Handle profile picture reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_profile'])) {
    $admin_id = $_SESSION['admin_id'];
    $default_image = "../Images/Profile/default-avatar.jpg";
    
    $sql = "UPDATE admins SET image=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $default_image, $admin_id);
    
    if ($stmt->execute()) {
        $_SESSION['admin_image'] = $default_image; // Update session with default image
        $_SESSION['success_msg'] = "Profile picture reset to default!";
    } else {
        $_SESSION['error_msg'] = "Error resetting profile picture!";
    }
    
    header('Location: profile.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $admin_id = $_SESSION['admin_id'];
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $middle_init = mysqli_real_escape_string($conn, $_POST['middle_init']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Handle file upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $target_dir = "../Images/Profile/";
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = "profile_" . $admin_id . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check !== false && move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
            $sql = "UPDATE admins SET firstname=?, middle_init=?, lastname=?, email=?, image=?, last_update=CURRENT_DATE WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $firstname, $middle_init, $lastname, $email, $image_path, $admin_id);
            
            // Update session image path if update successful
            if ($stmt->execute()) {
                $_SESSION['admin_firstname'] = $firstname;
                $_SESSION['admin_lastname'] = $lastname;
                $_SESSION['admin_image'] = $image_path; // Add this line to update session image
                $_SESSION['success_msg'] = "Profile updated successfully!";
            } else {
                $_SESSION['error_msg'] = "Error updating profile!";
            }
        }
    } else {
        $sql = "UPDATE admins SET firstname=?, middle_init=?, lastname=?, email=?, last_update=CURRENT_DATE WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $firstname, $middle_init, $lastname, $email, $admin_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['admin_firstname'] = $firstname;
        $_SESSION['admin_lastname'] = $lastname;
        $_SESSION['success_msg'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_msg'] = "Error updating profile!";
    }
    
    header('Location: profile.php');
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $admin_id = $_SESSION['admin_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $sql = "SELECT password FROM admins WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE admins SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $admin_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success_msg'] = "Password changed successfully!";
            } else {
                $_SESSION['error_msg'] = "Error changing password!";
            }
        } else {
            $_SESSION['error_msg'] = "New passwords do not match!";
        }
    } else {
        $_SESSION['error_msg'] = "Current password is incorrect!";
    }
    
    header('Location: profile.php');
    exit();
}

// Get admin data
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Get last login
$sql = "SELECT * FROM updates WHERE user_id = ? AND role = ? AND (status = 'Active login' OR status = 'Inactive login') ORDER BY `update` DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $admin_id, $_SESSION['role']);
$stmt->execute();
$result = $stmt->get_result();
$last_login = $result->fetch_assoc();

include('inc/header.php');
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-4">
            <!-- Profile picture card-->
            <div class="card mb-4 mb-xl-0">
                <div class="card-header">Profile Picture</div>
                <div class="card-body text-center">
                    <img class="img-account-profile rounded-circle mb-2" 
                         src="<?php echo $admin['image']; ?>" 
                         alt="" 
                         style="width: 180px; height: 180px; object-fit: cover;">
                    <div class="small font-italic text-muted mb-4">JPG or PNG no larger than 5 MB</div>
                    <!-- Add Reset Profile Picture button -->
                    <form method="POST" class="mb-3">
                        <button type="submit" name="reset_profile" class="btn btn-secondary btn-sm" 
                                onclick="return confirm('Are you sure you want to reset your profile picture to default?');">
                            Reset Profile Picture
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <!-- Account details card-->
            <div class="card mb-4">
                <div class="card-header">Account Details</div>
                <div class="card-body">
                    <?php if(isset($_SESSION['success_msg'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['error_msg'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <!-- Form Group (username)-->
                        <div class="mb-3">
                            <label class="small mb-1" for="inputEmployeeID">Employee ID</label>
                            <input class="form-control" id="inputEmployeeID" type="text" value="<?php echo $admin['employee_id']; ?>" disabled>
                        </div>
                        <!-- Form Row-->
                        <div class="row gx-3 mb-3">
                            <div class="col-md-4">
                                <label class="small mb-1" for="inputFirstName">First name</label>
                                <input class="form-control" id="inputFirstName" type="text" name="firstname" value="<?php echo $admin['firstname']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small mb-1" for="inputMiddleInit">Middle Initial</label>
                                <input class="form-control" id="inputMiddleInit" type="text" name="middle_init" value="<?php echo $admin['middle_init']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="small mb-1" for="inputLastName">Last name</label>
                                <input class="form-control" id="inputLastName" type="text" name="lastname" value="<?php echo $admin['lastname']; ?>" required>
                            </div>
                        </div>
                        <!-- Form Group (email address)-->
                        <div class="mb-3">
                            <label class="small mb-1" for="inputEmailAddress">Email address</label>
                            <input class="form-control" id="inputEmailAddress" type="email" name="email" value="<?php echo $admin['email']; ?>" required>
                        </div>
                        <!-- Form Group (profile picture)-->
                        <div class="mb-3">
                            <label class="small mb-1" for="inputProfilePicture">Profile Picture</label>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-light" onclick="document.getElementById('inputProfilePicture').click();">
                                    Choose File
                                </button>
                                <span class="ml-2 small text-muted" id="fileNameDisplay">No file chosen</span>
                                <input class="d-none" id="inputProfilePicture" type="file" name="profile_image" accept="image/*" onchange="updateFileName(this)">
                            </div>
                        </div>
                        <!-- Save changes button-->
                        <button class="btn btn-primary" type="submit" name="update_profile">Save changes</button>
                    </form>

                    <hr class="my-4">

                    <!-- Change Password Form -->
                    <form method="POST">
                        <div class="mb-3">
                            <label class="small mb-1" for="currentPassword">Current Password</label>
                            <input class="form-control" id="currentPassword" type="password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="small mb-1" for="newPassword">New Password</label>
                            <input class="form-control" id="newPassword" type="password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="small mb-1" for="confirmPassword">Confirm New Password</label>
                            <input class="form-control" id="confirmPassword" type="password" name="confirm_password" required>
                        </div>
                        <button class="btn btn-primary" type="submit" name="change_password">Change Password</button>
                    </form>

                    <hr class="my-4">

                    <!-- Last Login Information -->
                    <div class="small text-muted">
                        Last login: <?php echo $last_login ? date('F j, Y g:i a', strtotime($last_login['update'])) : 'Never'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- Add this script before the closing body tag -->
<script>
function updateFileName(input) {
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    if (input.files && input.files[0]) {
        fileNameDisplay.textContent = input.files[0].name;
    } else {
        fileNameDisplay.textContent = 'No file chosen';
    }
}
</script>

<?php include('inc/footer.php'); ?>
