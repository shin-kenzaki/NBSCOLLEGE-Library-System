<?php
ob_start();
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection and header
include '../db.php';
include '../admin/inc/header.php';

// Handle bulk delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['ids']) && !empty($_POST['ids'])) {
        $safeIds = array_map('intval', $_POST['ids']);
        $idsString = implode(',', $safeIds);
        $deleted_details = [];

        // Fetch details before deleting
        if (!empty($idsString)) {
            $fetchDetailsSql = "SELECT cc.id, b.title as book_title, c.name as corporate_name, cc.role 
                                FROM corporate_contributors cc
                                JOIN books b ON cc.book_id = b.id
                                JOIN corporates c ON cc.corporate_id = c.id
                                WHERE cc.id IN ($idsString)";
            $detailsResult = $conn->query($fetchDetailsSql);
            if ($detailsResult && $detailsResult->num_rows > 0) {
                while ($row = $detailsResult->fetch_assoc()) {
                    $deleted_details[$row['id']] = htmlspecialchars($row['corporate_name'] . ' (' . $row['role'] . ') for "' . $row['book_title'] . '"');
                }
            }
        }

        $conn->begin_transaction();
        try {
            $deleteCount = 0;
            $successfully_deleted_list = [];
            $stmt = $conn->prepare("DELETE FROM corporate_contributors WHERE id = ?");
            
            foreach ($safeIds as $id) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute() && $conn->affected_rows > 0) {
                    $deleteCount++;
                    if (isset($deleted_details[$id])) {
                        $successfully_deleted_list[] = $deleted_details[$id];
                    }
                }
            }
            $stmt->close();
            $conn->commit();
            
            $_SESSION['success_message'] = "$deleteCount corporate contributor record(s) deleted successfully.";
            $_SESSION['deleted_corporate_contributors_details'] = $successfully_deleted_list;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error deleting corporate contributors: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "No corporate contributor IDs provided for deletion.";
    }

    // Redirect to refresh the page
    header("Location: corporate_contributors.php");
    exit();
}

// Query to fetch corporate contributors data
$query = "SELECT 
            GROUP_CONCAT(cc.id ORDER BY cc.id) as id_ranges,
            b.title as book_title,
            c.name as corporate_name,
            cc.role,
            COUNT(cc.id) as total_entries
          FROM corporate_contributors cc
          JOIN books b ON cc.book_id = b.id
          JOIN corporates c ON cc.corporate_id = c.id
          GROUP BY b.title, c.id, cc.role
          ORDER BY b.title";

$result = $conn->query($query);
$corporate_contributors_data = array();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format ID ranges
        $ids = explode(',', $row['id_ranges']);
        $ranges = [];
        $start = $ids[0];
        $prev = $ids[0];
        
        for ($i = 1; $i < count($ids); $i++) {
            if ($ids[$i] - $prev > 1) {
                $ranges[] = $start == $prev ? $start : "$start-$prev";
                $start = $ids[$i];
            }
            $prev = $ids[$i];
        }
        $ranges[] = $start == $prev ? $start : "$start-$prev";
        
        $row['id_ranges'] = implode(', ', $ranges);
        $corporate_contributors_data[] = $row;
    }
}

// Get books for add modal
$booksQuery = "SELECT id, title FROM books ORDER BY title";
$booksResult = $conn->query($booksQuery);
$books = [];
while ($book = $booksResult->fetch_assoc()) {
    $books[] = $book;
}

// Get corporates for add modal
$corporatesQuery = "SELECT id, name FROM corporates ORDER BY name";
$corporatesResult = $conn->query($corporatesQuery);
$corporates = [];
while ($corporate = $corporatesResult->fetch_assoc()) {
    $corporates[] = $corporate;
}
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Corporate Contributors List</h6>
                <div class="d-flex align-items-center">
                    <button id="deleteSelectedBtn" class="btn btn-danger btn-sm mr-2 bulk-delete-btn" disabled>
                        <i class="fas fa-trash"></i>
                        <span>Delete Selected</span>
                        <span class="badge badge-light ml-1">0</span>
                    </button>
                </div>
            </div>
            <div class="card-body px-0">
                <div class="table-responsive px-3">
                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="text-align: center;" id="checkboxHeader">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th style="text-align: center;">ID</th>
                                <th style="text-align: center;">Book Title</th>
                                <th style="text-align: center;">Corporate Name</th>
                                <th style="text-align: center;">Role</th>
                                <th style="text-align: center;">Total Entries</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($corporate_contributors_data as $row): ?>
                            <tr>
                                <td style='text-align: center;'><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($row['id_ranges']); ?>"></td>
                                <td style='text-align: center;'><?php echo htmlspecialchars($row['id_ranges']); ?></td>
                                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['corporate_name']); ?></td>
                                <td style='text-align: center;'><?php echo htmlspecialchars($row['role']); ?></td>
                                <td style='text-align: center;'><?php echo htmlspecialchars($row['total_entries']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End of Main Content -->

<!-- Add Corporate Contributor Modal -->
<div class="modal fade" id="addCorporateContributorModal" tabindex="-1" role="dialog" aria-labelledby="addCorporateContributorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addCorporateContributorModalLabel">Add Corporate Contributor</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addCorporateContributorForm" method="POST" action="add_corporate_contributor.php">
                    <div class="form-group">
                        <label for="book_id">Book</label>
                        <select class="form-control" name="book_id" id="book_id" required>
                            <option value="">Select Book</option>
                            <?php foreach ($books as $book): ?>
                            <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="corporate_id">Corporate</label>
                        <select class="form-control" name="corporate_id" id="corporate_id" required>
                            <option value="">Select Corporate</option>
                            <?php foreach ($corporates as $corporate): ?>
                            <option value="<?php echo $corporate['id']; ?>"><?php echo htmlspecialchars($corporate['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select class="form-control" name="role" id="role" required>
                            <option value="">Select Role</option>
                            <option value="Publisher">Publisher</option>
                            <option value="Sponsoring Institution">Sponsoring Institution</option>
                            <option value="Distributor">Distributor</option>
                            <option value="Collaborator">Collaborator</option>
                            <option value="Research Institution">Research Institution</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveCorporateContributor">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include '../Admin/inc/footer.php'; ?>
<!-- End of Footer -->

<script>
$(document).ready(function () {
    var selectedIds = [];

    // Handle select all checkbox
    $('#selectAll').on('change', function () {
        var isChecked = $(this).prop('checked');
        $('.row-checkbox').prop('checked', isChecked);
        selectedIds = isChecked ? $('.row-checkbox').map(function () { return $(this).val(); }).get() : [];
        updateDeleteButton();
    });

    // Handle individual checkbox changes
    $('#dataTable tbody').on('change', '.row-checkbox', function () {
        var idRange = $(this).val();
        if ($(this).prop('checked')) {
            selectedIds.push(idRange);
        } else {
            selectedIds = selectedIds.filter(item => item !== idRange);
        }
        $('#selectAll').prop('checked', $('.row-checkbox:checked').length === $('.row-checkbox').length);
        updateDeleteButton();
    });

    // Update delete button state
    function updateDeleteButton() {
        const count = selectedIds.length;
        $('#deleteSelectedBtn .badge').text(count);
        $('#deleteSelectedBtn').prop('disabled', count === 0);
    }

    // Parse ID ranges into individual IDs
    function parseIdRanges(idRanges) {
        const parsedIds = [];
        idRanges.forEach(range => {
            range.split(',').forEach(part => {
                part = part.trim();
                if (part.includes('-')) {
                    const [start, end] = part.split('-').map(Number);
                    for (let i = start; i <= end; i++) {
                        parsedIds.push(i);
                    }
                } else {
                    parsedIds.push(Number(part));
                }
            });
        });
        return parsedIds;
    }

    // Handle bulk delete button click with loading style
    $('#deleteSelectedBtn').on('click', function () {
        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Confirm Bulk Deletion',
            html: `Are you sure you want to delete <strong>${selectedIds.length}</strong> selected corporate contributor(s)?<br><br>
                   <span class="text-danger">This action cannot be undone!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting...',
                    html: 'Please wait while the selected corporate contributors are being deleted.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Parse ID ranges into individual IDs
                const parsedIds = parseIdRanges(selectedIds);

                // Submit the form with parsed IDs
                $.ajax({
                    url: 'corporate_contributors.php',
                    method: 'POST',
                    data: {
                        action: 'delete',
                        ids: parsedIds
                    },
                    success: function() {
                        location.reload(); // Reload the page to show updated list and messages
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while deleting the corporate contributors. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            }
        });
    });

    $('#saveCorporateContributor').click(function() {
        $('#addCorporateContributorForm').submit();
    });

    // Display session messages using SweetAlert2
    <?php if (isset($_SESSION['success_message'])): ?>
        <?php
        $message = addslashes($_SESSION['success_message']);
        $detailsList = '';
        // Check for deleted corporate contributor details
        if (isset($_SESSION['deleted_corporate_contributors_details']) && !empty($_SESSION['deleted_corporate_contributors_details'])) {
            $details = array_map(function($detail) {
                return htmlspecialchars($detail, ENT_QUOTES);
            }, $_SESSION['deleted_corporate_contributors_details']); // Sanitize details
            $detailsList = '<br><br><strong>Deleted Corporate Contributors:</strong><br>' . implode('<br>', $details);
            unset($_SESSION['deleted_corporate_contributors_details']); // Unset the deleted details list
        }
        ?>
        Swal.fire({
            title: 'Success!',
            html: '<?php echo $message . $detailsList; ?>', // Use html property for formatted content
            icon: 'success',
            confirmButtonColor: '#3085d6'
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?php echo addslashes($_SESSION['error_message']); ?>',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "responsive": true,
        "scrollX": true,
        "order": [[1, "asc"]],
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        }
    });
});
</script>
