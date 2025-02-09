<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
include '../db.php';

include '../Admin/inc/header.php';
// Fetch writers data
// Fetch admin details from database
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT firstname, middle_init, lastname, username, image, role, status, date_added, last_update FROM admins WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();



// If admin has no profile image, use a default avatar
$profile_image = !empty($admin['image']) ? "../Admin/inc/upload/" . $admin['image'] : "../Admin/inc/upload/default-avatar.jpg";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = $_POST['admin_id'];
    $firstname = $_POST['firstname'];
    $middle_init = $_POST['middle_init'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    // Image Upload Handling
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../Admin/inc/upload/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);

        // Update query with image
        $sql = "UPDATE admins SET firstname=?, middle_init=?, lastname=?, username=?, image=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $firstname, $middle_init, $lastname, $username, $image_name, $admin_id);
    } else {
        // Update query without changing image
        $sql = "UPDATE admins SET firstname=?, middle_init=?, lastname=?, username=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $firstname, $middle_init, $lastname, $username, $admin_id);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating profile.";
        header("Location: profile.php");
        exit();
    }
}


?>


<style>.img-profile {
    max-width: 150px; /* Max width */
    height: auto; /* Maintain aspect ratio */
    display: block;
    margin: 0 auto; /* Center the image */
}


</style>


            <!-- Main Content -->
            <div id="content" class="d-flex flex-column min-vh-100">
                <div class="container-fluid">

                    <!-- Page Heading -->



  <div class="container py-1">
    <div class="row">
      <div class="col">
        <nav aria-label="breadcrumb" class="bg-body-tertiary rounded-3 p-3 mb-4">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">User Profile</li>
          </ol>
        </nav>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-body text-center">
          <img class="img-profile rounded-circle img-fluid" src="<?php echo $admin['image']; ?>" alt="avatar" style="max-width: 75%; height: auto;">

            <h5 class="my-3"><p class="text-muted mb-0"><?php echo $admin['firstname'] . " " . $admin['middle_init'] . " " . $admin['lastname']; ?></p></h5>
            <p class="text-muted mb-0"><?php echo ucfirst($admin['role']); ?></p>
            <br>

            <div class="d-flex justify-content-center mb-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                Edit Profile
            </button>




            </div>
          </div>
        </div>

      </div>
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-body">
          <div class="row">
                        <div class="col-sm-3"><p class="mb-0">Full Name</p></div>
                        <div class="col-sm-9"><p class="text-muted mb-0"><?php echo $admin['firstname'] . " " . $admin['middle_init'] . " " . $admin['lastname']; ?></p></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><p class="mb-0">Username</p></div>
                        <div class="col-sm-9"><p class="text-muted mb-0"><?php echo $admin['username']; ?></p></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><p class="mb-0">Role</p></div>
                        <div class="col-sm-9"><p class="text-muted mb-0"><?php echo ucfirst($admin['role']); ?></p></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><p class="mb-0">Status</p></div>
                        <div class="col-sm-9"><p class="text-muted mb-0"><?php echo ucfirst($admin['status']); ?></p></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><p class="mb-0">Date Created</p></div>
                        <div class="col-sm-9"><p class="text-muted mb-0"><?php echo date("F d, Y", strtotime($admin['date_added'])); ?></p></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><p class="mb-0">Last Updated</p></div>
                        <div class="col-sm-9"><p class="text-muted mb-0"><?php echo date("F d, Y", strtotime($admin['last_update'])); ?></p></div>
                    </div>

          </div>
        </div>
      </div>
    </div>
  </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include '../Admin/inc/footer.php'?>
            <!-- End of Footer -->







    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
