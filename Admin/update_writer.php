<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../db.php';

$writerId = isset($_GET['writer_id']) ? intval($_GET['writer_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = $_POST['firstname'];
    $middle_init = $_POST['middle_init'];
    $lastname = $_POST['lastname'];

    $query = "UPDATE writers SET firstname = ?, middle_init = ?, lastname = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssi', $firstname, $middle_init, $lastname, $writerId);
    $stmt->execute();

    $_SESSION['success_message'] = "Writer updated successfully!";
    header("Location: writers_list.php");
    exit();
}

$query = "SELECT * FROM writers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $writerId);
$stmt->execute();
$result = $stmt->get_result();
$writer = $result->fetch_assoc();

if (!$writer) {
    echo "Writer not found!";
    exit();
}

include '../admin/inc/header.php';
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Update Writer</h6>
            </div>
            <div class="card-body">
                <form id="updateWriterForm" method="POST" action="update_writer.php?writer_id=<?php echo $writerId; ?>">
                    <input type="hidden" name="writer_id" value="<?php echo $writer['id']; ?>">
                    <div class="form-group">
                        <label for="updateFirstName">First Name</label>
                        <input type="text" class="form-control" name="firstname" id="updateFirstName" value="<?php echo htmlspecialchars($writer['firstname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="updateMiddleInit">Middle Initial</label>
                        <input type="text" class="form-control" name="middle_init" id="updateMiddleInit" value="<?php echo htmlspecialchars($writer['middle_init']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="updateLastName">Last Name</label>
                        <input type="text" class="form-control" name="lastname" id="updateLastName" value="<?php echo htmlspecialchars($writer['lastname']); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Main Content -->

<?php include '../admin/inc/footer.php'; ?>