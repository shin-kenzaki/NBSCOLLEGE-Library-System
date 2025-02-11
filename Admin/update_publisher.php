<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../db.php';

$publisherId = isset($_GET['publisher_id']) ? intval($_GET['publisher_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company = $_POST['company'];
    $place = $_POST['place'];

    $query = "UPDATE publishers SET company = ?, place = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssi', $company, $place, $publisherId);
    $stmt->execute();

    $_SESSION['success_message'] = "Publisher updated successfully!";
    header("Location: publisher_list.php");
    exit();
}

$query = "SELECT * FROM publishers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $publisherId);
$stmt->execute();
$result = $stmt->get_result();
$publisher = $result->fetch_assoc();

if (!$publisher) {
    echo "Publisher not found!";
    exit();
}

include '../admin/inc/header.php';
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Update Publisher</h6>
            </div>
            <div class="card-body">
                <form id="updatePublisherForm" method="POST" action="update_publisher.php?publisher_id=<?php echo $publisherId; ?>">
                    <input type="hidden" name="publisher_id" value="<?php echo $publisher['id']; ?>">
                    <div class="form-group">
                        <label for="updateCompany">Company</label>
                        <input type="text" class="form-control" name="company" id="updateCompany" value="<?php echo htmlspecialchars($publisher['company']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="updatePlace">Place</label>
                        <input type="text" class="form-control" name="place" id="updatePlace" value="<?php echo htmlspecialchars($publisher['place']); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Main Content -->

<?php include '../admin/inc/footer.php'; ?>