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
$page = isset($_GET['upage']) ? intval($_GET['upage']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$roleFilter = isset($_GET['urole']) ? $_GET['urole'] : '';
$dateStart = isset($_GET['udate_start']) ? $_GET['udate_start'] : '';
$dateEnd = isset($_GET['udate_end']) ? $_GET['udate_end'] : '';
$searchFilter = isset($_GET['usearch']) ? $_GET['usearch'] : '';
$statusFilter = isset($_GET['ustatus']) ? $_GET['ustatus'] : '';

// Initialize variables for storing combined results
$combinedResults = [];
$totalRecords = 0;

// Check if we're filtering for admins (directly or via role filter)
$includeAdmins = (!$roleFilter || in_array($roleFilter, ['Admin', 'Librarian', 'Assistant', 'Encoder']));

// Check if we're filtering for regular users
$includeUsers = (!$roleFilter || in_array($roleFilter, ['Student', 'Faculty', 'Staff', 'Visitor']));

// First, get admin data if needed
if ($includeAdmins) {
    // Build the SQL WHERE clause for filtering admins
    $adminWhereClause = "";
    $adminParams = [];

    if ($roleFilter && in_array($roleFilter, ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
        $adminWhereClause .= $adminWhereClause ? " AND role = '$roleFilter'" : "WHERE role = '$roleFilter'";
        $adminParams[] = "urole=$roleFilter";
    }

    if ($dateStart) {
        $adminWhereClause .= $adminWhereClause ? " AND date_added >= '$dateStart'" : "WHERE date_added >= '$dateStart'";
        $adminParams[] = "udate_start=$dateStart";
    }

    if ($dateEnd) {
        $adminWhereClause .= $adminWhereClause ? " AND date_added <= '$dateEnd'" : "WHERE date_added <= '$dateEnd'";
        $adminParams[] = "udate_end=$dateEnd";
    }

    if ($searchFilter) {
        $adminWhereClause .= $adminWhereClause ? " AND (firstname LIKE '%$searchFilter%' OR lastname LIKE '%$searchFilter%' OR employee_id LIKE '%$searchFilter%' OR email LIKE '%$searchFilter%')" : 
                                   "WHERE (firstname LIKE '%$searchFilter%' OR lastname LIKE '%$searchFilter%' OR employee_id LIKE '%$searchFilter%' OR email LIKE '%$searchFilter%')";
        $adminParams[] = "usearch=" . urlencode($searchFilter);
    }

    if ($statusFilter !== '') {
        $adminWhereClause .= $adminWhereClause ? " AND status = '$statusFilter'" : "WHERE status = '$statusFilter'";
        $adminParams[] = "ustatus=$statusFilter";
    }

    // Count total admins matching filters
    $adminCountSql = "SELECT COUNT(*) as total FROM admins $adminWhereClause";
    $adminCountResult = mysqli_query($conn, $adminCountSql);
    $adminCount = mysqli_fetch_assoc($adminCountResult)['total'];
    $totalRecords += $adminCount;

    // Get admin data - only if they would appear in the current page
    if ($adminCount > 0) {
        $adminSql = "SELECT id, employee_id as user_id, firstname, lastname, email, role as usertype, 
                    date_added, status, 'admin' as source
                    FROM admins
                    $adminWhereClause
                    ORDER BY date_added DESC";
        $adminResult = mysqli_query($conn, $adminSql);
        
        while ($row = mysqli_fetch_assoc($adminResult)) {
            $combinedResults[] = $row;
        }
    }
}

// Get regular users data if needed
if ($includeUsers) {
    // Build the SQL WHERE clause for filtering users
    $userWhereClause = "";
    $userParams = [];

    if ($roleFilter && in_array($roleFilter, ['Student', 'Faculty', 'Staff', 'Visitor'])) {
        $userWhereClause .= $userWhereClause ? " AND usertype = '$roleFilter'" : "WHERE usertype = '$roleFilter'";
        $userParams[] = "urole=$roleFilter";
    }

    if ($dateStart) {
        $userWhereClause .= $userWhereClause ? " AND date_added >= '$dateStart'" : "WHERE date_added >= '$dateStart'";
        $userParams[] = "udate_start=$dateStart";
    }

    if ($dateEnd) {
        $userWhereClause .= $userWhereClause ? " AND date_added <= '$dateEnd'" : "WHERE date_added <= '$dateEnd'";
        $userParams[] = "udate_end=$dateEnd";
    }

    if ($searchFilter) {
        $userWhereClause .= $userWhereClause ? " AND (firstname LIKE '%$searchFilter%' OR lastname LIKE '%$searchFilter%' OR school_id LIKE '%$searchFilter%' OR email LIKE '%$searchFilter%')" : 
                                "WHERE (firstname LIKE '%$searchFilter%' OR lastname LIKE '%$searchFilter%' OR school_id LIKE '%$searchFilter%' OR email LIKE '%$searchFilter%')";
        $userParams[] = "usearch=" . urlencode($searchFilter);
    }

    if ($statusFilter !== '') {
        $userWhereClause .= $userWhereClause ? " AND status = '$statusFilter'" : "WHERE status = '$statusFilter'";
        $userParams[] = "ustatus=$statusFilter";
    }

    // Count total users matching filters
    $userCountSql = "SELECT COUNT(*) as total FROM users $userWhereClause";
    $userCountResult = mysqli_query($conn, $userCountSql);
    $userCount = mysqli_fetch_assoc($userCountResult)['total'];
    $totalRecords += $userCount;

    // Get user data - only if they would appear in the current page
    if ($userCount > 0) {
        $userSql = "SELECT id, school_id as user_id, firstname, lastname, email, usertype, 
                   date_added, status, 'user' as source
                   FROM users
                   $userWhereClause
                   ORDER BY date_added DESC";
        $userResult = mysqli_query($conn, $userSql);
        
        while ($row = mysqli_fetch_assoc($userResult)) {
            $combinedResults[] = $row;
        }
    }
}

// Sort combined results by date_added (newest first)
usort($combinedResults, function($a, $b) {
    return strtotime($b['date_added']) - strtotime($a['date_added']);
});

// Apply pagination to combined results
$totalPages = ceil($totalRecords / $recordsPerPage);
$paginatedResults = array_slice($combinedResults, $offset, $recordsPerPage);

// Build table HTML
ob_start();
?>
<table class="table table-bordered table-striped" id="usersDataTable" width="100%" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Email</th>
            <th>Date Added</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($paginatedResults) > 0): ?>
            <?php foreach ($paginatedResults as $row): ?>
                <tr>
                    <td><?= $row['user_id'] ?></td>
                    <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                    <td>
                        <?php
                            $typeClass = '';
                            $userType = $row['usertype'];
                            
                            if ($row['source'] === 'admin') {
                                // Admin roles
                                switch($userType) {
                                    case 'Admin':
                                        $typeClass = 'badge-danger';
                                        break;
                                    case 'Librarian':
                                        $typeClass = 'badge-primary';
                                        break;
                                    case 'Assistant':
                                        $typeClass = 'badge-info';
                                        break;
                                    case 'Encoder':
                                        $typeClass = 'badge-secondary';
                                        break;
                                }
                            } else {
                                // Regular user roles
                                switch($userType) {
                                    case 'Student':
                                        $typeClass = 'badge-success';
                                        break;
                                    case 'Faculty':
                                        $typeClass = 'badge-warning';
                                        break;
                                    case 'Staff':
                                        $typeClass = 'badge-dark';
                                        break;
                                    case 'Visitor':
                                        $typeClass = 'badge-light';
                                        break;
                                }
                            }
                        ?>
                        <span class="badge <?= $typeClass ?>"><?= $userType ?></span>
                    </td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= date('M d, Y', strtotime($row['date_added'])) ?></td>
                    <td>
                        <?php if ($row['status'] == '1'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">No user records found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
$tableHtml = ob_get_clean();

// Build pagination HTML
ob_start();
if ($totalPages > 1): ?>
<nav aria-label="Users pagination">
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link upagination-link" href="#" data-page="<?= $page-1 ?>" tabindex="-1">Previous</a>
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
            echo '<a class="page-link upagination-link" href="#" data-page="1">1</a></li>';
            
            // Show ellipsis if needed
            if ($startPage > 2) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            }
        }
        
        // Loop through the visible page range
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i > 1 && $i < $totalPages) {  // Skip first and last page since they're handled separately
                echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                echo '<a class="page-link upagination-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
            }
        }
        
        // Always show last page
        if ($endPage < $totalPages) {
            // Show ellipsis if needed
            if ($endPage < $totalPages - 1) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            }
            
            echo '<li class="page-item ' . ($page == $totalPages ? 'active' : '') . '">';
            echo '<a class="page-link upagination-link" href="#" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
        }
        ?>
        
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link upagination-link" href="#" data-page="<?= $page+1 ?>">Next</a>
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
