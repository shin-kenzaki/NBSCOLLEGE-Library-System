<?php
session_start();
require('../db.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle profile picture reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_profile'])) {
    $user_id = $_SESSION['user_id'];
    $default_image = "../Images/Profile/default-avatar.jpg";
    
    $sql = "UPDATE users SET user_image=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $default_image, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['user_image'] = $default_image; // Update session with default image
        $_SESSION['success_msg'] = "Profile picture reset to default!";
    } else {
        $_SESSION['error_msg'] = "Error resetting profile picture!";
    }
    
    header('Location: profile.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $user_id = $_SESSION['user_id'];
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $middle_init = mysqli_real_escape_string($conn, $_POST['middle_init']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $id_type = mysqli_real_escape_string($conn, $_POST['id_type']);

    // Get existing user data first to access image paths
    $get_user_sql = "SELECT user_image, id_image FROM users WHERE id = ?";
    $get_user_stmt = $conn->prepare($get_user_sql);
    $get_user_stmt->bind_param("i", $user_id);
    $get_user_stmt->execute();
    $user_data = $get_user_stmt->get_result()->fetch_assoc();
    
    $profile_image_path = $user_data['user_image'];
    $id_image_path = $user_data['id_image'];

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $target_dir = "../Images/Profile/";
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = "profile_" . $user_id . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check !== false && move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image_path = $target_file;
        }
    }

    // Handle ID image upload
    if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === 0) {
        $target_dir = "../Images/ID/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["id_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = "id_" . $user_id . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        $check = getimagesize($_FILES["id_image"]["tmp_name"]);
        if ($check !== false && move_uploaded_file($_FILES["id_image"]["tmp_name"], $target_file)) {
            $id_image_path = $target_file;
        }
    }

    $sql = "UPDATE users SET firstname=?, middle_init=?, lastname=?, email=?, contact_no=?, address=?, id_type=?, user_image=?, id_image=?, last_update=CURRENT_DATE WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssi", $firstname, $middle_init, $lastname, $email, $contact_no, $address, $id_type, $profile_image_path, $id_image_path, $user_id);

    if ($stmt->execute()) {
        $_SESSION['user_firstname'] = $firstname;
        $_SESSION['user_lastname'] = $lastname;
        $_SESSION['user_image'] = $profile_image_path;
        $_SESSION['success_msg'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_msg'] = "Error updating profile!";
    }

    header('Location: profile.php');
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_password = $result->fetch_assoc();  // Changed variable name here
    
    if (password_verify($current_password, $user_password['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
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

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT u.*, 
       (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status IN ('Borrowed', 'Overdue')) AS borrowed_books,
       (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status = 'Returned') AS returned_books,
       (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status = 'Damaged') AS damaged_books,
       (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status = 'Lost') AS lost_books,
       (SELECT COUNT(*) FROM reservations WHERE user_id = u.id) AS total_reservations,
       (SELECT COUNT(*) FROM reservations WHERE user_id = u.id AND status = 'Reserved') AS active_reservations,
       (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id) AS total_transactions
       FROM users u WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get recent activity
$activity_sql = "SELECT * FROM updates WHERE user_id = ? AND role = 'Student' ORDER BY `update` DESC LIMIT 5";
$activity_stmt = $conn->prepare($activity_sql);
$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();
$activity_result = $activity_stmt->get_result();

include('inc/header.php');
?>

<div id="content" class="d-flex flex-column min-vh-100">
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-4">
                <!-- Profile picture card-->
                <div class="card mb-4 mb-xl-0">
                    <div class="card-header">Profile Picture</div>
                    <div class="card-body text-center">
                        <img class="img-account-profile rounded-circle mb-2" 
                            src="<?php echo $user['user_image']; ?>" 
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
                
                <!-- ID Document Preview Card -->
                <div class="card mt-4">
                    <div class="card-header">ID Document</div>
                    <div class="card-body text-center">
                        <?php if (!empty($user['id_image'])): ?>
                            <img class="img-fluid mb-2" 
                                src="<?php echo $user['id_image']; ?>" 
                                alt="ID Document" 
                                style="max-height: 200px; max-width: 100%;">
                            <div class="small font-italic text-muted">
                                <?php echo ucfirst($user['id_type'] ? $user['id_type'] : 'No ID type specified'); ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted py-4">
                                <i class="fas fa-id-card fa-3x mb-3"></i>
                                <p>No ID document uploaded</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <!-- Account details card with tabs -->
                <div class="card mb-4">
                    <div class="card-header">
                        <!-- Tabs -->
                        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="true">Profile Details</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="account-tab" data-toggle="tab" href="#account" role="tab" aria-controls="account" aria-selected="false">Account Details</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="password-tab" data-toggle="tab" href="#password" role="tab" aria-controls="password" aria-selected="false">Change Password</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="stats-tab" data-toggle="tab" href="#stats" role="tab" aria-controls="stats" aria-selected="false">Statistics</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <?php if(isset($_SESSION['success_msg'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['error_msg'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                        <?php endif; ?>

                        <div class="tab-content" id="profileTabsContent">
                            <!-- Profile Details Tab -->
                            <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                <form method="POST" enctype="multipart/form-data">
                                    <!-- Form Group (username)-->
                                    <div class="mb-3">
                                        <label class="small mb-1" for="inputSchoolID">School ID</label>
                                        <input class="form-control" id="inputSchoolID" type="text" value="<?php echo $user['school_id']; ?>" disabled>
                                    </div>
                                    <!-- Form Row-->
                                    <div class="row gx-3 mb-3">
                                        <div class="col-md-4">
                                            <label class="small mb-1" for="inputFirstName">First name</label>
                                            <input class="form-control" id="inputFirstName" type="text" name="firstname" value="<?php echo $user['firstname']; ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small mb-1" for="inputMiddleInit">Middle Initial</label>
                                            <input class="form-control" id="inputMiddleInit" type="text" name="middle_init" value="<?php echo $user['middle_init']; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small mb-1" for="inputLastName">Last name</label>
                                            <input class="form-control" id="inputLastName" type="text" name="lastname" value="<?php echo $user['lastname']; ?>" required>
                                        </div>
                                    </div>
                                    <!-- Form Group (email address)-->
                                    <div class="mb-3">
                                        <label class="small mb-1" for="inputEmailAddress">Email address</label>
                                        <input class="form-control" id="inputEmailAddress" type="email" name="email" value="<?php echo $user['email']; ?>" readonly>
                                    </div>
                                    <!-- Form Group (contact number)-->
                                    <div class="mb-3">
                                        <label class="small mb-1" for="inputContactNo">Contact Number</label>
                                        <input class="form-control" id="inputContactNo" type="text" name="contact_no" value="<?php echo $user['contact_no']; ?>">
                                    </div>
                                    <!-- Form Group (address)-->
                                    <div class="mb-3">
                                        <label class="small mb-1" for="inputAddress">Address</label>
                                        <input class="form-control" id="inputAddress" type="text" name="address" value="<?php echo $user['address']; ?>">
                                    </div>
                                    <!-- Form Group (id type)-->
                                    <div class="mb-3">
                                        <label class="small mb-1" for="inputIDType">ID Type</label>
                                        <select class="form-control" id="inputIDType" name="id_type">
                                            <option value="">Select ID Type</option>
                                            <option value="passport" <?php if($user['id_type']=='passport') echo 'selected'; ?>>Passport</option>
                                            <option value="sss" <?php if($user['id_type']=='sss') echo 'selected'; ?>>SSS</option>
                                            <option value="philhealth" <?php if($user['id_type']=='philhealth') echo 'selected'; ?>>PhilHealth</option>
                                            <option value="tin" <?php if($user['id_type']=='tin') echo 'selected'; ?>>TIN</option>
                                            <option value="school" <?php if($user['id_type']=='school') echo 'selected'; ?>>School</option>
                                            <option value="national id" <?php if($user['id_type']=='national id') echo 'selected'; ?>>National ID</option>
                                            <option value="drivers licence" <?php if($user['id_type']=='drivers licence') echo 'selected'; ?>>Driver's License</option>
                                        </select>
                                    </div>
                                    <!-- Form Group (id image and profile picture in one row)-->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="small mb-1" for="inputIDImage">ID Image</label>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($user['id_image'])): ?>
                                                    <img src="<?php echo $user['id_image']; ?>" alt="ID Image" style="width: 80px; height: 50px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px;">
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-light" onclick="document.getElementById('inputIDImage').click();">
                                                    Choose File
                                                </button>
                                                <span class="ml-2 small text-muted" id="idFileNameDisplay">No file chosen</span>
                                                <input class="d-none" id="inputIDImage" type="file" name="id_image" accept="image/*" onchange="updateIDFileName(this)">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small mb-1" for="inputProfilePicture">Profile Picture</label>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($user['user_image'])): ?>
                                                    <img src="<?php echo $user['user_image']; ?>" alt="Profile Image" style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%; margin-right: 10px;">
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-light" onclick="document.getElementById('inputProfilePicture').click();">
                                                    Choose File
                                                </button>
                                                <span class="ml-2 small text-muted" id="fileNameDisplay">No file chosen</span>
                                                <input class="d-none" id="inputProfilePicture" type="file" name="profile_image" accept="image/*" onchange="updateFileName(this)">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Save changes button-->
                                    <div class="mb-3 text-center">
                                        <div class="small text-muted mb-2">
                                            <i class="fas fa-info-circle mr-1"></i> Only First Name and Last Name are required fields
                                        </div>
                                        <button class="btn btn-primary btn-lg" type="submit" name="update_profile">
                                            <i class="fas fa-save mr-2"></i> Save Profile Changes
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Account Details Tab (New) -->
                            <div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
                                <div class="card-body">
                                    <h5 class="mb-4">Account Information</h5>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4 font-weight-bold">Account Status:</div>
                                        <div class="col-md-8">
                                            <?php if($user['status'] == '1'): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4 font-weight-bold">Registration Date:</div>
                                        <div class="col-md-8"><?php echo date('F j, Y', strtotime($user['date_added'])); ?></div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4 font-weight-bold">Last Profile Update:</div>
                                        <div class="col-md-8">
                                            <?php echo $user['last_update'] ? date('F j, Y', strtotime($user['last_update'])) : 'Never updated'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4 font-weight-bold">Account Type:</div>
                                        <div class="col-md-8"><?php echo $user['usertype']; ?></div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4 font-weight-bold">Department:</div>
                                        <div class="col-md-8"><?php echo $user['department'] ? $user['department'] : 'Not specified'; ?></div>
                                    </div>
                                    
                                    <hr>
                                    <h5 class="mb-4">Recent Activity</h5>
                                    
                                    <?php if ($activity_result->num_rows > 0): ?>
                                        <div class="list-group">
                                            <?php while ($activity = $activity_result->fetch_assoc()): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?php echo $activity['title']; ?></h6>
                                                        <small><?php echo date('M j, Y g:i a', strtotime($activity['update'])); ?></small>
                                                    </div>
                                                    <p class="mb-1"><?php echo $activity['message']; ?></p>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No recent activity found.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Change Password Tab -->
                            <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
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
                            </div>

                            <!-- Statistics Tab -->
                            <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
                                <div class="card-body">
                                    <h5 class="mb-3">Your Library Statistics</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body">
                                                    <h5 class="card-title">Books currently borrowed</h5>
                                                    <h2 class="display-4"><?php echo $user['borrowed_books']; ?></h2>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body">
                                                    <h5 class="card-title">Books returned</h5>
                                                    <h2 class="display-4"><?php echo $user['returned_books']; ?></h2>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-warning text-dark">
                                                <div class="card-body">
                                                    <h5 class="card-title">Damaged books</h5>
                                                    <h2 class="display-4"><?php echo $user['damaged_books']; ?></h2>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-danger text-white">
                                                <div class="card-body">
                                                    <h5 class="card-title">Lost books</h5>
                                                    <h2 class="display-4"><?php echo $user['lost_books']; ?></h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h5 class="mb-3 mt-4">Detailed Statistics</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th>Total Library Transactions</th>
                                                    <td><?php echo $user['total_transactions']; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Total Reservations Made</th>
                                                    <td><?php echo $user['total_reservations']; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Active Reservations</th>
                                                    <td><?php echo $user['active_reservations']; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Books Currently Borrowed</th>
                                                    <td><?php echo $user['borrowed_books']; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Return Rate</th>
                                                    <td>
                                                        <?php 
                                                            $total = $user['returned_books'] + $user['damaged_books'] + $user['lost_books'];
                                                            echo $total > 0 ? round(($user['returned_books'] / $total) * 100, 2) . '%' : 'N/A';
                                                        ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
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
function updateIDFileName(input) {
    const fileNameDisplay = document.getElementById('idFileNameDisplay');
    if (input.files && input.files[0]) {
        fileNameDisplay.textContent = input.files[0].name;
    } else {
        fileNameDisplay.textContent = 'No file chosen';
    }
}

// Auto-capitalize name fields
document.addEventListener('DOMContentLoaded', function() {
    // Add input event listeners to name fields
    const firstNameInput = document.getElementById('inputFirstName');
    const middleInitInput = document.getElementById('inputMiddleInit');
    const lastNameInput = document.getElementById('inputLastName');
    
    if (firstNameInput) {
        firstNameInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
    
    if (middleInitInput) {
        middleInitInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
    
    if (lastNameInput) {
        lastNameInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});
</script>

<?php include('inc/footer.php'); ?>
