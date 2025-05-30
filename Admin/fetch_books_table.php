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
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$programFilter = isset($_GET['program']) ? $_GET['program'] : '';
$subjectCategoryFilter = isset($_GET['subject_category']) ? $_GET['subject_category'] : '';
$titleFilter = isset($_GET['title']) ? $_GET['title'] : '';
$locationFilter = isset($_GET['location']) ? $_GET['location'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= $whereClause ? " AND b.status = '$statusFilter'" : "WHERE b.status = '$statusFilter'";
    $filterParams[] = "status=$statusFilter";
}

if ($programFilter) {
    $whereClause .= $whereClause ? " AND b.program = '$programFilter'" : "WHERE b.program = '$programFilter'";
    $filterParams[] = "program=$programFilter";
}

if ($subjectCategoryFilter) {
    $whereClause .= $whereClause ? " AND b.subject_category = '$subjectCategoryFilter'" : "WHERE b.subject_category = '$subjectCategoryFilter'";
    $filterParams[] = "subject_category=$subjectCategoryFilter";
}

if ($titleFilter) {
    $whereClause .= $whereClause ? " AND (b.title LIKE '%$titleFilter%' OR b.accession LIKE '%$titleFilter%')" : 
                               "WHERE (b.title LIKE '%$titleFilter%' OR b.accession LIKE '%$titleFilter%')";
    $filterParams[] = "title=" . urlencode($titleFilter);
}

if ($locationFilter) {
    $whereClause .= $whereClause ? " AND b.shelf_location LIKE '%$locationFilter%'" : 
                               "WHERE b.shelf_location LIKE '%$locationFilter%'";
    $filterParams[] = "location=" . urlencode($locationFilter);
}

// Count total books for pagination with filters
$countSql = "SELECT COUNT(*) as total FROM books b $whereClause";
         
$countResult = mysqli_query($conn, $countSql);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get books with additional details
$booksSql = "SELECT b.id, b.accession, b.title, b.status, b.shelf_location, b.date_added, b.ISBN,
                w.firstname AS writer_firstname, w.middle_init AS writer_middle_init, w.lastname AS writer_lastname,
                p.publisher, p.place AS publisher_place, pub.publish_date,
                a.firstname AS admin_firstname, a.lastname AS admin_lastname, a.role AS admin_role
                FROM books b
                LEFT JOIN contributors c ON b.id = c.book_id AND c.role = 'Author'
                LEFT JOIN writers w ON c.writer_id = w.id
                LEFT JOIN publications pub ON b.id = pub.book_id
                LEFT JOIN publishers p ON pub.publisher_id = p.id
                LEFT JOIN admins a ON b.entered_by = a.id
                $whereClause
                ORDER BY b.date_added DESC
                LIMIT $offset, $recordsPerPage";
$booksResult = mysqli_query($conn, $booksSql);

// Build table HTML
ob_start();
?>
<table class="table table-bordered table-striped" id="booksDataTable" width="100%" cellspacing="0">
    <thead>
        <tr>
            <th>Accession</th>
            <th>Title</th>
            <th>Author</th>
            <th>Publisher</th>
            <th>Status</th>
            <th>Location</th>
            <th>Added On</th>
            <th>Added By</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($booksResult) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($booksResult)): ?>
                <tr>
                    <td><?= $row['accession'] ?></td>
                    <td>
                        <?= htmlspecialchars($row['title']) ?>
                        <?php if ($row['ISBN']): ?>
                            <br><small class="text-muted">ISBN: <?= $row['ISBN'] ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['writer_firstname'] || $row['writer_lastname']): ?>
                            <?= htmlspecialchars($row['writer_firstname'] . ' ' . ($row['writer_middle_init'] ? $row['writer_middle_init'] . ' ' : '') . $row['writer_lastname']) ?>
                        <?php else: ?>
                            <span class="text-muted">Not specified</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['publisher']): ?>
                            <?= htmlspecialchars($row['publisher']) ?>
                            <?php if ($row['publisher_place']): ?>
                                <br><small class="text-muted"><?= $row['publisher_place'] ?></small>
                            <?php endif; ?>
                            <?php if ($row['publish_date']): ?>
                                <br><small class="text-muted"><?= $row['publish_date'] ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">Not specified</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                            $statusClass = '';
                            switch($row['status']) {
                                case 'Available':
                                    $statusClass = 'badge-success';
                                    break;
                                case 'Borrowed':
                                    $statusClass = 'badge-primary';
                                    break;
                                case 'Reserved':
                                    $statusClass = 'badge-warning';
                                    break;
                                case 'Damaged':
                                    $statusClass = 'badge-danger';
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
                    <td><?= htmlspecialchars($row['shelf_location']) ?></td>
                    <td><?= date('M d, Y', strtotime($row['date_added'])) ?></td>
                    <td>
                        <?= htmlspecialchars($row['admin_firstname'] . ' ' . $row['admin_lastname']) ?>
                        <br><small class="text-muted">(<?= $row['admin_role'] ?>)</small>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center">No books found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
$tableHtml = ob_get_clean();

// Build pagination HTML
ob_start();
if ($totalPages > 1): ?>
<nav aria-label="Books pagination">
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link bookpagination-link" href="#" data-page="<?= $page-1 ?>" tabindex="-1">Previous</a>
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
            echo '<a class="page-link bookpagination-link" href="#" data-page="1">1</a></li>';
            
            // Show ellipsis if needed
            if ($startPage > 2) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            }
        }
        
        // Loop through the visible page range
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i > 1 && $i < $totalPages) {  // Skip first and last page since they're handled separately
                echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                echo '<a class="page-link bookpagination-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
            }
        }
        
        // Always show last page
        if ($endPage < $totalPages) {
            // Show ellipsis if needed
            if ($endPage < $totalPages - 1) {
                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            }
            
            echo '<li class="page-item ' . ($page == $totalPages ? 'active' : '') . '">';
            echo '<a class="page-link bookpagination-link" href="#" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
        }
        ?>
        
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link bookpagination-link" href="#" data-page="<?= $page+1 ?>">Next</a>
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
