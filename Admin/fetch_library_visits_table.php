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
$page = isset($_GET['vpage']) ? intval($_GET['vpage']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$courseFilter = isset($_GET['vcourse']) ? $_GET['vcourse'] : '';
$dateStart = isset($_GET['vdate_start']) ? $_GET['vdate_start'] : '';
$dateEnd = isset($_GET['vdate_end']) ? $_GET['vdate_end'] : '';
$userFilter = isset($_GET['vuser']) ? $_GET['vuser'] : '';
$purposeFilter = isset($_GET['vpurpose']) ? $_GET['vpurpose'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($courseFilter) {
    $whereClause .= $whereClause ? " AND u.department = '$courseFilter'" : "WHERE u.department = '$courseFilter'";
    $filterParams[] = "vcourse=$courseFilter";
}

if ($dateStart) {
    $whereClause .= $whereClause ? " AND DATE(lv.time) >= '$dateStart'" : "WHERE DATE(lv.time) >= '$dateStart'";
    $filterParams[] = "vdate_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= $whereClause ? " AND DATE(lv.time) <= '$dateEnd'" : "WHERE DATE(lv.time) <= '$dateEnd'";
    $filterParams[] = "vdate_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= $whereClause ? " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')" : 
                               "WHERE (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "vuser=" . urlencode($userFilter);
}

if ($purposeFilter) {
    $whereClause .= $whereClause ? " AND lv.purpose LIKE '%$purposeFilter%'" : "WHERE lv.purpose LIKE '%$purposeFilter%'";
    $filterParams[] = "vpurpose=" . urlencode($purposeFilter);
}

// Count total visits for pagination with filters
$countSql = "SELECT COUNT(*) as total FROM library_visits lv 
         LEFT JOIN users u ON lv.student_number = u.school_id
         $whereClause";
         
$countResult = mysqli_query($conn, $countSql);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get library visits with user details
$visitsSql = "SELECT lv.id, lv.time, lv.purpose, lv.status, 
                u.school_id, u.firstname as user_firstname, u.lastname as user_lastname, u.usertype, u.department
                FROM library_visits lv
                LEFT JOIN users u ON lv.student_number = u.school_id
                $whereClause
                ORDER BY lv.time DESC
                LIMIT $offset, $recordsPerPage";
$visitsResult = mysqli_query($conn, $visitsSql);

// Build table HTML
ob_start();
?>
<table class="table table-bordered table-striped" id="visitsTable" width="100%" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Visitor</th>
            <th>Course/Dept</th>
            <th>Visit Time</th>
            <th>Purpose</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($visitsResult) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($visitsResult)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                        <?= htmlspecialchars($row['user_firstname'] . ' ' . $row['user_lastname']) ?>
                        <br><small class="text-muted"><?= $row['school_id'] ?> (<?= $row['usertype'] ?>)</small>
                    </td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($row['time'])) ?></td>
                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                    <td>
                        <?php if ($row['status'] == 1): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Closed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">No library visit records found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
$tableHtml = ob_get_clean();

// Build pagination HTML
ob_start();
if ($totalPages > 1): ?>
<nav aria-label="Visits pagination">
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link vpagination-link" href="#" data-page="<?= $page-1 ?>" tabindex="-1">Previous</a>
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
            echo '<a class="page-link vpagination-link" href="#" data-page="1">1</a></li>';
            
            // Show ellipsis if needed
            if ($startPage > 2) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            }
        }
        
        // Loop through the visible page range
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i > 1 && $i < $totalPages) {  // Skip first and last page since they're handled separately
                echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                echo '<a class="page-link vpagination-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
            }
        }
        
        // Always show last page
        if ($endPage < $totalPages) {
            // Show ellipsis if needed
            if ($endPage < $totalPages - 1) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            }
            
            echo '<li class="page-item ' . ($page == $totalPages ? 'active' : '') . '">';
            echo '<a class="page-link vpagination-link" href="#" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
        }
        ?>
        
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link vpagination-link" href="#" data-page="<?= $page+1 ?>">Next</a>
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
