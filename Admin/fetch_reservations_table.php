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
$page = isset($_GET['rpage']) ? intval($_GET['rpage']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$statusFilter = isset($_GET['rstatus']) ? $_GET['rstatus'] : '';
$dateStart = isset($_GET['rdate_start']) ? $_GET['rdate_start'] : '';
$dateEnd = isset($_GET['rdate_end']) ? $_GET['rdate_end'] : '';
$userFilter = isset($_GET['ruser']) ? $_GET['ruser'] : '';
$bookFilter = isset($_GET['rbook']) ? $_GET['rbook'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= $whereClause ? " AND r.status = '$statusFilter'" : "WHERE r.status = '$statusFilter'";
    $filterParams[] = "rstatus=$statusFilter";
}

if ($dateStart) {
    $whereClause .= $whereClause ? " AND DATE(r.reserve_date) >= '$dateStart'" : "WHERE DATE(r.reserve_date) >= '$dateStart'";
    $filterParams[] = "rdate_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= $whereClause ? " AND DATE(r.reserve_date) <= '$dateEnd'" : "WHERE DATE(r.reserve_date) <= '$dateEnd'";
    $filterParams[] = "rdate_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= $whereClause ? " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')" : 
                               "WHERE (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "ruser=" . urlencode($userFilter);
}

if ($bookFilter) {
    $whereClause .= $whereClause ? " AND (bk.title LIKE '%$bookFilter%' OR bk.accession LIKE '%$bookFilter%')" : 
                               "WHERE (bk.title LIKE '%$bookFilter%' OR bk.accession LIKE '%$bookFilter%')";
    $filterParams[] = "rbook=" . urlencode($bookFilter);
}

// Count total reservations for pagination with filters
$countSql = "SELECT COUNT(*) as total FROM reservations r 
         LEFT JOIN users u ON r.user_id = u.id
         LEFT JOIN books bk ON r.book_id = bk.id
         $whereClause";
         
$countResult = mysqli_query($conn, $countSql);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get recent reservations with user and book details
$reservationsSql = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date,
                  u.school_id, u.firstname as user_firstname, u.lastname as user_lastname, u.usertype,
                  bk.accession, bk.title, 
                  a1.firstname as ready_admin_firstname, a1.lastname as ready_admin_lastname, a1.role as ready_admin_role,
                  a2.firstname as issued_admin_firstname, a2.lastname as issued_admin_lastname, a2.role as issued_admin_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC
                  LIMIT $offset, $recordsPerPage";
$reservationsResult = mysqli_query($conn, $reservationsSql);

// Build table HTML
ob_start();
?>
<table class="table table-bordered table-striped" id="reservationsTable" width="100%" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Book</th>
            <th>Status</th>
            <th>Reserved On</th>
            <th>Ready On</th>
            <th>Received On</th>
            <th>Staff</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($reservationsResult) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($reservationsResult)): ?>
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
                    <td>
                        <?php
                            $statusClass = '';
                            switch($row['status']) {
                                case 'Pending':
                                    $statusClass = 'badge-warning';
                                    break;
                                case 'Ready':
                                    $statusClass = 'badge-info';
                                    break;
                                case 'Recieved':
                                    $statusClass = 'badge-success';
                                    break;
                                case 'Cancelled':
                                    $statusClass = 'badge-danger';
                                    break;
                                default:
                                    $statusClass = 'badge-secondary';
                            }
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= $row['status'] ?></span>
                    </td>
                    <td><?= date('M d, Y', strtotime($row['reserve_date'])) ?></td>
                    <td><?= $row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-' ?></td>
                    <td><?= $row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-' ?></td>
                    <td><?= $row['ready_date'] ? htmlspecialchars($row['ready_admin_firstname'] . ' ' . $row['ready_admin_lastname']) . 
                            '<br><small class="text-muted">(' . $row['ready_admin_role'] . ')</small>' : '-' ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center">No reservation records found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
$tableHtml = ob_get_clean();

// Build pagination HTML
ob_start();
if ($totalPages > 1): ?>
<nav aria-label="Reservations pagination">
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link rpagination-link" href="#" data-page="<?= $page-1 ?>" tabindex="-1">Previous</a>
        </li>
        
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                <a class="page-link rpagination-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link rpagination-link" href="#" data-page="<?= $page+1 ?>">Next</a>
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
