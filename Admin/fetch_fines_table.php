<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get filter parameters
$page = isset($_GET['fpage']) ? intval($_GET['fpage']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$statusFilter = isset($_GET['fstatus']) ? $_GET['fstatus'] : '';
$dateStart = isset($_GET['fdate_start']) ? $_GET['fdate_start'] : '';
$dateEnd = isset($_GET['fdate_end']) ? $_GET['fdate_end'] : '';
$userFilter = isset($_GET['fuser']) ? $_GET['fuser'] : '';
$typeFilter = isset($_GET['ftype']) ? $_GET['ftype'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= $whereClause ? " AND f.status = '$statusFilter'" : "WHERE f.status = '$statusFilter'";
    $filterParams[] = "fstatus=$statusFilter";
}

if ($dateStart) {
    $whereClause .= $whereClause ? " AND f.date >= '$dateStart'" : "WHERE f.date >= '$dateStart'";
    $filterParams[] = "fdate_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= $whereClause ? " AND f.date <= '$dateEnd'" : "WHERE f.date <= '$dateEnd'";
    $filterParams[] = "fdate_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= $whereClause ? " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')" : 
                               "WHERE (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "fuser=" . urlencode($userFilter);
}

if ($typeFilter) {
    $whereClause .= $whereClause ? " AND f.type = '$typeFilter'" : "WHERE f.type = '$typeFilter'";
    $filterParams[] = "ftype=$typeFilter";
}

// Count total fines for pagination with filters
$countSql = "SELECT COUNT(*) as total FROM fines f 
             LEFT JOIN borrowings b ON f.borrowing_id = b.id
             LEFT JOIN users u ON b.user_id = u.id
             LEFT JOIN books bk ON b.book_id = bk.id
             $whereClause";
             
$countResult = mysqli_query($conn, $countSql);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get fines data with user and book details
$finesSql = "SELECT f.id, f.type, f.amount, f.status, f.date, f.payment_date,
                b.id as borrowing_id,
                u.school_id, u.firstname as user_firstname, u.lastname as user_lastname, u.usertype,
                bk.accession, bk.title
                FROM fines f
                LEFT JOIN borrowings b ON f.borrowing_id = b.id
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN books bk ON b.book_id = bk.id
                $whereClause
                ORDER BY f.date DESC
                LIMIT $offset, $recordsPerPage";
$finesResult = mysqli_query($conn, $finesSql);

// Build table HTML
ob_start();
?>
<table class="table table-bordered table-striped" id="finesDataTable" width="100%" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Borrower</th>
            <th>Book</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Payment Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($finesResult) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($finesResult)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                        <?= htmlspecialchars($row['user_firstname'] . ' ' . $row['user_lastname']) ?>
                        <br><small class="text-muted"><?= $row['school_id'] ?> (<?= $row['usertype'] ?>)</small>
                    </td>
                    <td>
                        <?= htmlspecialchars($row['title']) ?>
                        <br><small class="text-muted">Accession: <?= $row['accession'] ?></small>
                    </td>
                    <td><?= $row['type'] ?></td>
                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                    <td>
                        <?php
                            $statusClass = '';
                            switch($row['status']) {
                                case 'Paid':
                                    $statusClass = 'badge-success';
                                    break;
                                case 'Unpaid':
                                    $statusClass = 'badge-danger';
                                    break;
                                default:
                                    $statusClass = 'badge-secondary';
                            }
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= $row['status'] ?></span>
                    </td>
                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                    <td><?= $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : '-' ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center">No fine records found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
$tableHtml = ob_get_clean();

// Build pagination HTML
ob_start();
if ($totalPages > 1): ?>
<nav aria-label="Fines pagination">
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link fpagination-link" href="#" data-page="<?= $page-1 ?>" tabindex="-1">Previous</a>
        </li>
        
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                <a class="page-link fpagination-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link fpagination-link" href="#" data-page="<?= $page+1 ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif;
$paginationHtml = ob_get_clean();

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'tableHtml' => $tableHtml,
    'paginationHtml' => $paginationHtml,
    'totalRecords' => $totalRecords,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?>
