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
$page = isset($_GET['bpage']) ? intval($_GET['bpage']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$statusFilter = isset($_GET['bstatus']) ? $_GET['bstatus'] : '';
$dateStart = isset($_GET['bdate_start']) ? $_GET['bdate_start'] : '';
$dateEnd = isset($_GET['bdate_end']) ? $_GET['bdate_end'] : '';
$userFilter = isset($_GET['buser']) ? $_GET['buser'] : '';
$bookFilter = isset($_GET['bbook']) ? $_GET['bbook'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= $whereClause ? " AND b.status = '$statusFilter'" : "WHERE b.status = '$statusFilter'";
    $filterParams[] = "bstatus=$statusFilter";
}

if ($dateStart) {
    $whereClause .= $whereClause ? " AND b.issue_date >= '$dateStart'" : "WHERE b.issue_date >= '$dateStart'";
    $filterParams[] = "bdate_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= $whereClause ? " AND b.issue_date <= '$dateEnd'" : "WHERE b.issue_date <= '$dateEnd'";
    $filterParams[] = "bdate_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= $whereClause ? " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')" : 
                               "WHERE (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "buser=" . urlencode($userFilter);
}

if ($bookFilter) {
    $whereClause .= $whereClause ? " AND (bk.title LIKE '%$bookFilter%' OR bk.accession LIKE '%$bookFilter%')" : 
                               "WHERE (bk.title LIKE '%$bookFilter%' OR bk.accession LIKE '%$bookFilter%')";
    $filterParams[] = "bbook=" . urlencode($bookFilter);
}

// Count total borrowings for pagination with filters
$countSql = "SELECT COUNT(*) as total FROM borrowings b 
         LEFT JOIN users u ON b.user_id = u.id
         LEFT JOIN books bk ON b.book_id = bk.id
         $whereClause";
         
$countResult = mysqli_query($conn, $countSql);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get recent borrowings with user and book details
$borrowingsSql = "SELECT b.id, b.status, b.issue_date, b.due_date, b.return_date, b.report_date, 
                u.school_id, u.firstname as user_firstname, u.lastname as user_lastname, u.usertype,
                bk.accession, bk.title, 
                a.firstname as admin_firstname, a.lastname as admin_lastname, a.role as admin_role
                FROM borrowings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN books bk ON b.book_id = bk.id
                LEFT JOIN admins a ON b.issued_by = a.id
                $whereClause
                ORDER BY b.issue_date DESC
                LIMIT $offset, $recordsPerPage";
$borrowingsResult = mysqli_query($conn, $borrowingsSql);

// Build table HTML
ob_start();
?>
<table class="table table-bordered table-striped" id="borrowingsTable" width="100%" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Borrower</th>
            <th>Book</th>
            <th>Status</th>
            <th>Issue Date</th>
            <th>Due Date</th>
            <th>Return Date</th>
            <th>Issued By</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($borrowingsResult) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($borrowingsResult)): ?>
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
                                case 'Active':
                                    $statusClass = 'badge-primary';
                                    break;
                                case 'Returned':
                                    $statusClass = 'badge-success';
                                    break;
                                case 'Damaged':
                                    $statusClass = 'badge-warning';
                                    break;
                                case 'Lost':
                                    $statusClass = 'badge-danger';
                                    break;
                                default:
                                    $statusClass = 'badge-secondary';
                            }
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= $row['status'] ?></span>
                    </td>
                    <td><?= date('M d, Y', strtotime($row['issue_date'])) ?></td>
                    <td><?= $row['due_date'] ? date('M d, Y', strtotime($row['due_date'])) : '-' ?></td>
                    <td><?= $row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : '-' ?></td>
                    <td><?= htmlspecialchars($row['admin_firstname'] . ' ' . $row['admin_lastname']) ?>
                        <br><small class="text-muted">(<?= $row['admin_role'] ?>)</small>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center">No borrowing records found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
$tableHtml = ob_get_clean();

// Build pagination HTML
ob_start();
if ($totalPages > 1): ?>
<nav aria-label="Borrowings pagination">
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link pagination-link" href="#" data-page="<?= $page-1 ?>" tabindex="-1">Previous</a>
        </li>
        
        <?php
        // Show limited number of pages with ellipses
        $maxVisiblePages = 5;
        $halfVisible = floor($maxVisiblePages/2);
        
        // Calculate start and end page numbers to display
        if ($totalPages <= $maxVisiblePages) {
            // If we have fewer pages than our max, show all of them
            $startPage = 1;
            $endPage = $totalPages;
        } else {
            // Calculate which pages to show based on current page
            $startPage = max(1, $page - $halfVisible);
            $endPage = min($totalPages, $page + $halfVisible);
            
            // Adjust if we're near the beginning or end
            if ($startPage == 1) {
                $endPage = $maxVisiblePages;
            } else if ($endPage == $totalPages) {
                $startPage = $totalPages - $maxVisiblePages + 1;
            }
        }
        
        // Always show first page
        if ($startPage > 1) {
            echo '<li class="page-item ' . ($page == 1 ? 'active' : '') . '">';
            echo '<a class="page-link pagination-link" href="#" data-page="1">1</a></li>';
            
            // Show ellipsis if needed
            if ($startPage > 2) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            }
        }
        
        // Loop through the visible page range
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i > 1 && $i < $totalPages) {  // Skip first and last page since they're handled separately
                echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                echo '<a class="page-link pagination-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
            }
        }
        
        // Always show last page
        if ($endPage < $totalPages) {
            // Show ellipsis if needed
            if ($endPage < $totalPages - 1) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            }
            
            echo '<li class="page-item ' . ($page == $totalPages ? 'active' : '') . '">';
            echo '<a class="page-link pagination-link" href="#" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
        }
        ?>
        
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link pagination-link" href="#" data-page="<?= $page+1 ?>">Next</a>
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
