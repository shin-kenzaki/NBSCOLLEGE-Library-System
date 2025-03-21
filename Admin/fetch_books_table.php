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
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$titleFilter = isset($_GET['title']) ? $_GET['title'] : '';
$locationFilter = isset($_GET['location']) ? $_GET['location'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= $whereClause ? " AND b.status = '$statusFilter'" : "WHERE b.status = '$statusFilter'";
    $filterParams[] = "status=$statusFilter";
}

if ($dateStart) {
    $whereClause .= $whereClause ? " AND b.date_added >= '$dateStart'" : "WHERE b.date_added >= '$dateStart'";
    $filterParams[] = "date_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= $whereClause ? " AND b.date_added <= '$dateEnd'" : "WHERE b.date_added <= '$dateEnd'";
    $filterParams[] = "date_end=$dateEnd";
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
        
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                <a class="page-link bookpagination-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        
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
