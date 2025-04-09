<?php
session_start();
require_once '../db.php';

// Check if the user is logged in with appropriate permissions
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian'])) {
    header("Location: index.php");
    exit();
}

// Get export type
$exportType = isset($_GET['type']) ? $_GET['type'] : '';

// Set filename and title based on export type
switch ($exportType) {
    case 'borrowings':
        $filename = 'borrowings_report_' . date('Y-m-d') . '.pdf';
        $title = 'Borrowings Report';
        break;
    case 'reservations':
        $filename = 'reservations_report_' . date('Y-m-d') . '.pdf';
        $title = 'Reservations Report';
        break;
    case 'users':
        $filename = 'users_report_' . date('Y-m-d') . '.pdf';
        $title = 'Users Report';
        break;
    case 'books':
        $filename = 'books_report_' . date('Y-m-d') . '.pdf';
        $title = 'Books Inventory Report';
        break;
    case 'fines':
        $filename = 'fines_report_' . date('Y-m-d') . '.pdf';
        $title = 'Fines Report';
        break;
    case 'library_visits':
        $filename = 'library_visits_report_' . date('Y-m-d') . '.pdf';
        $title = 'Library Visits Report';
        break;
    default:
        die("Invalid export type specified.");
}

// Include TCPDF library - use the correct path to TCPDF
// Try multiple paths to handle different installations
if (file_exists('tcpdf/tcpdf.php')) {
    require_once('tcpdf/tcpdf.php');
} elseif (file_exists('../vendor/tecnickcom/tcpdf/tcpdf.php')) {
    require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');
} elseif (file_exists('vendor/tecnickcom/tcpdf/tcpdf.php')) {
    require_once('vendor/tecnickcom/tcpdf/tcpdf.php');
} elseif (file_exists('../tcpdf/tcpdf.php')) {
    require_once('../tcpdf/tcpdf.php');
} else {
    // Try using Composer autoloader if available
    require_once 'vendor/autoload.php';
    // If we got here and TCPDF is still not loaded, we need to stop
    if (!class_exists('TCPDF')) {
        die("TCPDF library not found. Please check the installation.");
    }
}

// Ensure no output is sent before generating the PDF
ob_start();

// Update the margin settings to be dynamic based on page number
class MYPDF extends TCPDF {
    public function Header() {
        // Only show header on first page
        if ($this->PageNo() == 1) {
            global $title;
            
            // Calculate position for centered logo
            $pageWidth = $this->GetPageWidth() - 40;
            $imageWidth = 40;
            $xPos = ($pageWidth - $imageWidth) / 2 + 20;
            
            // Logo - use the horizontal NBS logo
            $image_file = 'inc/img/horizontal-nbs-logo.png';
            if (file_exists($image_file)) {
                $this->Image($image_file, $xPos, $this->GetY(), $imageWidth);
            }
            
            // Add some space after logo
            $this->Ln(20);
            
            // Add NBS COLLEGE LIBRARY text
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 5, 'NBS COLLEGE LIBRARY', 0, 1, 'C');
            
            // Add report title immediately after
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell(0, 10, $title, 0, 1, 'C');

            // Set margin for first page only
            $this->SetMargins(15, 40, 15);
        } else {
            // Set full margins for subsequent pages
            $this->SetMargins(15, 15, 15);
        }
    }

    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        // Date generated
        $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('NBSC Library System');
$pdf->SetAuthor('NBSC Library');
$pdf->SetTitle('NBSC Library - ' . $title);
$pdf->SetSubject($title);
$pdf->SetKeywords('Library, Report, ' . $exportType);

// Set default header and footer data
$pdf->setHeaderFont(Array('helvetica', '', 10));
$pdf->setFooterFont(Array('helvetica', '', 8));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set initial margins - will be adjusted per page in Header()
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage('L', 'A4');

// Set font
$pdf->SetFont('helvetica', '', 10);

// Add filter summary section with improved styling (single line format)
$filterSummary = '<h3 style="color:#4e73df;border-bottom:1px solid #e3e6f0;padding-bottom:5px;">Filter Parameters:</h3>';

// Generate data and add content based on export type
switch ($exportType) {
    case 'borrowings':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary in a single line
        $filterParams = [];
        if ($status) $filterParams[] = "Status: $status";
        if ($dateStart) $filterParams[] = "From Date: $dateStart";
        if ($dateEnd) $filterParams[] = "To Date: $dateEnd";
        if ($user) $filterParams[] = "Borrower: $user";
        if ($book) $filterParams[] = "Book: $book";
        
        if (empty($filterParams)) {
            $filterSummary .= "<p>No filters applied</p>";
        } else {
            $filterSummary .= "<p>" . implode(" | ", $filterParams) . "</p>";
        }
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.issue_date >= ?" : "WHERE b.issue_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.issue_date <= ?" : "WHERE b.issue_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get borrowing data with filters
        $query = "SELECT b.id, b.status, b.issue_date, b.due_date, b.return_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS borrower_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a.firstname, ' ', a.lastname) AS issued_by, a.role
                  FROM borrowings b
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN books bk ON b.book_id = bk.id
                  LEFT JOIN admins a ON b.issued_by = a.id
                  $whereClause
                  ORDER BY b.issue_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header with column headers but without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Borrower</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Issue Date</th>
                            <th align="center">Due Date</th>
                            <th align="center">Return Date</th>
                            <th align="center">Issued By</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Active': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Returned': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['borrower_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['issue_date'])) . '</td>
                            <td>' . ($row['due_date'] ? date('M d, Y', strtotime($row['due_date'])) : '-') . '</td>
                            <td>' . ($row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : '-') . '</td>
                            <td>' . htmlspecialchars($row['issued_by']) . '<br><small style="color:#858796;">' . $row['role'] . '</small></td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No borrowing records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'books':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Build filter summary in a single line
        $filterParams = [];
        if ($status) $filterParams[] = "Status: $status";
        if ($dateStart) $filterParams[] = "From Date: $dateStart";
        if ($dateEnd) $filterParams[] = "To Date: $dateEnd";
        if ($title) $filterParams[] = "Title/Accession: $title";
        if ($location) $filterParams[] = "Location: $location";
        
        if (empty($filterParams)) {
            $filterSummary .= "<p>No filters applied</p>";
        } else {
            $filterSummary .= "<p>" . implode(" | ", $filterParams) . "</p>";
        }
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.date_added >= ?" : "WHERE b.date_added >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.date_added <= ?" : "WHERE b.date_added <= ?";
            $params[] = $dateEnd;
        }
        
        if ($title) {
            $whereClause .= $whereClause ? " AND (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)" :
                                          "WHERE (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)";
            $titleParam = "%$title%";
            $params[] = $titleParam;
            $params[] = $titleParam;
            $params[] = $titleParam;
        }
        
        if ($location) {
            $whereClause .= $whereClause ? " AND b.shelf_location LIKE ?" :
                                          "WHERE b.shelf_location LIKE ?";
            $locationParam = "%$location%";
            $params[] = $locationParam;
        }
        
        // Query to get book data with filters - FIXED with correct column names
        $query = "SELECT b.id, b.accession, b.title, 
                         p.publisher as publisher, YEAR(b.date_added) as publication_year, b.isbn, 
                         b.subject_category, b.subject_detail, b.shelf_location, b.status, b.date_added,
                         CONCAT(a.firstname, ' ', a.lastname) AS added_by, a.role,
                         GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author
                  FROM books b
                  LEFT JOIN admins a ON b.entered_by = a.id
                  LEFT JOIN publications pub ON b.id = pub.book_id
                  LEFT JOIN publishers p ON pub.publisher_id = p.id
                  LEFT JOIN contributors c ON b.id = c.book_id
                  LEFT JOIN writers w ON c.writer_id = w.id
                  $whereClause
                  GROUP BY b.id, b.accession, b.title, p.publisher, b.date_added, b.isbn, 
                           b.subject_category, b.subject_detail, b.shelf_location, b.status, a.firstname, a.lastname, a.role
                  ORDER BY b.date_added DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Accession</th>
                            <th align="center">Title</th>
                            <th align="center">Author</th>
                            <th align="center">ISBN</th>
                            <th align="center">Category</th>
                            <th align="center">Location</th>
                            <th align="center">Status</th>
                            <th align="center">Added</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Available': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Borrowed': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Reserved': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Format location - use the correct column
                $location = $row['shelf_location'];
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['accession'] . '</td>
                            <td>' . htmlspecialchars($row['title']) . '</td>
                            <td>' . htmlspecialchars($row['author'] ?: 'Not specified') . '</td>
                            <td>' . $row['isbn'] . '</td>
                            <td>' . $row['subject_category'] . '</td>
                            <td>' . htmlspecialchars($location ?: 'Not specified') . '</td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No book records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="9" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary in a single line
        $filterParams = [];
        if ($status) $filterParams[] = "Status: $status";
        if ($dateStart) $filterParams[] = "From Date: $dateStart";
        if ($dateEnd) $filterParams[] = "To Date: $dateEnd";
        if ($user) $filterParams[] = "User: $user";
        if ($book) $filterParams[] = "Book: $book";
        
        if (empty($filterParams)) {
            $filterSummary .= "<p>No filters applied</p>";
        } else {
            $filterSummary .= "<p>" . implode(" | ", $filterParams) . "</p>";
        }
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary in a single line
        $filterParams = [];
        if ($role) $filterParams[] = "User Type: $role";
        if ($dateStart) $filterParams[] = "From Date: $dateStart";
        if ($dateEnd) $filterParams[] = "To Date: $dateEnd";
        if ($search) $filterParams[] = "Search Term: $search";
        if ($status !== '') $filterParams[] = "Status: " . ($status == '1' ? 'Active' : 'Inactive');
        
        if (empty($filterParams)) {
            $filterSummary .= "<p>No filters applied</p>";
        } else {
            $filterSummary .= "<p>" . implode(" | ", $filterParams) . "</p>";
        }
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '" nobr="true">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td style="page-break-inside: avoid;">' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;

case 'books':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($title) $filterSummary .= "<li>Title/Accession: $title</li>";
        if ($location) $filterSummary .= "<li>Location: $location</li>";
        if (!$status && !$dateStart && !$dateEnd && !$title && !$location) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.date_added >= ?" : "WHERE b.date_added >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.date_added <= ?" : "WHERE b.date_added <= ?";
            $params[] = $dateEnd;
        }
        
        if ($title) {
            $whereClause .= $whereClause ? " AND (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)" :
                                          "WHERE (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)";
            $titleParam = "%$title%";
            $params[] = $titleParam;
            $params[] = $titleParam;
            $params[] = $titleParam;
        }
        
        if ($location) {
            $whereClause .= $whereClause ? " AND b.shelf_location LIKE ?" :
                                          "WHERE b.shelf_location LIKE ?";
            $locationParam = "%$location%";
            $params[] = $locationParam;
        }
        
        // Query to get book data with filters - FIXED with correct column names
        $query = "SELECT b.id, b.accession, b.title, 
                         p.publisher as publisher, YEAR(b.date_added) as publication_year, b.isbn, 
                         b.subject_category, b.subject_detail, b.shelf_location, b.status, b.date_added,
                         CONCAT(a.firstname, ' ', a.lastname) AS added_by, a.role,
                         GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author
                  FROM books b
                  LEFT JOIN admins a ON b.entered_by = a.id
                  LEFT JOIN publications pub ON b.id = pub.book_id
                  LEFT JOIN publishers p ON pub.publisher_id = p.id
                  LEFT JOIN contributors c ON b.id = c.book_id
                  LEFT JOIN writers w ON c.writer_id = w.id
                  $whereClause
                  GROUP BY b.id, b.accession, b.title, p.publisher, b.date_added, b.isbn, 
                           b.subject_category, b.subject_detail, b.shelf_location, b.status, a.firstname, a.lastname, a.role
                  ORDER BY b.date_added DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Accession</th>
                            <th align="center">Title</th>
                            <th align="center">Author</th>
                            <th align="center">ISBN</th>
                            <th align="center">Category</th>
                            <th align="center">Location</th>
                            <th align="center">Status</th>
                            <th align="center">Added</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Available': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Borrowed': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Reserved': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Format location - use the correct column
                $location = $row['shelf_location'];
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['accession'] . '</td>
                            <td>' . htmlspecialchars($row['title']) . '</td>
                            <td>' . htmlspecialchars($row['author'] ?: 'Not specified') . '</td>
                            <td>' . $row['isbn'] . '</td>
                            <td>' . $row['subject_category'] . '</td>
                            <td>' . htmlspecialchars($location ?: 'Not specified') . '</td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No book records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="9" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($user) $filterSummary .= "<li>User: $user</li>";
        if ($book) $filterSummary .= "<li>Book: $book</li>";
        if (!$status && !$dateStart && !$dateEnd && !$user && !$book) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($role) $filterSummary .= "<li>User Type: $role</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($search) $filterSummary .= "<li>Search Term: $search</li>";
        if ($status !== '') $filterSummary .= "<li>Status: " . ($status == '1' ? 'Active' : 'Inactive') . "</li>";
        if (!$role && !$dateStart && !$dateEnd && !$search && $status === '') {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td>' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;

case 'books':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($title) $filterSummary .= "<li>Title/Accession: $title</li>";
        if ($location) $filterSummary .= "<li>Location: $location</li>";
        if (!$status && !$dateStart && !$dateEnd && !$title && !$location) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.date_added >= ?" : "WHERE b.date_added >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.date_added <= ?" : "WHERE b.date_added <= ?";
            $params[] = $dateEnd;
        }
        
        if ($title) {
            $whereClause .= $whereClause ? " AND (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)" :
                                          "WHERE (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)";
            $titleParam = "%$title%";
            $params[] = $titleParam;
            $params[] = $titleParam;
            $params[] = $titleParam;
        }
        
        if ($location) {
            $whereClause .= $whereClause ? " AND b.shelf_location LIKE ?" :
                                          "WHERE b.shelf_location LIKE ?";
            $locationParam = "%$location%";
            $params[] = $locationParam;
        }
        
        // Query to get book data with filters - FIXED with correct column names
        $query = "SELECT b.id, b.accession, b.title, 
                         p.publisher as publisher, YEAR(b.date_added) as publication_year, b.isbn, 
                         b.subject_category, b.subject_detail, b.shelf_location, b.status, b.date_added,
                         CONCAT(a.firstname, ' ', a.lastname) AS added_by, a.role,
                         GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author
                  FROM books b
                  LEFT JOIN admins a ON b.entered_by = a.id
                  LEFT JOIN publications pub ON b.id = pub.book_id
                  LEFT JOIN publishers p ON pub.publisher_id = p.id
                  LEFT JOIN contributors c ON b.id = c.book_id
                  LEFT JOIN writers w ON c.writer_id = w.id
                  $whereClause
                  GROUP BY b.id, b.accession, b.title, p.publisher, b.date_added, b.isbn, 
                           b.subject_category, b.subject_detail, b.shelf_location, b.status, a.firstname, a.lastname, a.role
                  ORDER BY b.date_added DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Accession</th>
                            <th align="center">Title</th>
                            <th align="center">Author</th>
                            <th align="center">ISBN</th>
                            <th align="center">Category</th>
                            <th align="center">Location</th>
                            <th align="center">Status</th>
                            <th align="center">Added</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Available': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Borrowed': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Reserved': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Format location - use the correct column
                $location = $row['shelf_location'];
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['accession'] . '</td>
                            <td>' . htmlspecialchars($row['title']) . '</td>
                            <td>' . htmlspecialchars($row['author'] ?: 'Not specified') . '</td>
                            <td>' . $row['isbn'] . '</td>
                            <td>' . $row['subject_category'] . '</td>
                            <td>' . htmlspecialchars($location ?: 'Not specified') . '</td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No book records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="9" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($user) $filterSummary .= "<li>User: $user</li>";
        if ($book) $filterSummary .= "<li>Book: $book</li>";
        if (!$status && !$dateStart && !$dateEnd && !$user && !$book) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($role) $filterSummary .= "<li>User Type: $role</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($search) $filterSummary .= "<li>Search Term: $search</li>";
        if ($status !== '') $filterSummary .= "<li>Status: " . ($status == '1' ? 'Active' : 'Inactive') . "</li>";
        if (!$role && !$dateStart && !$dateEnd && !$search && $status === '') {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td>' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;

case 'books':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($title) $filterSummary .= "<li>Title/Accession: $title</li>";
        if ($location) $filterSummary .= "<li>Location: $location</li>";
        if (!$status && !$dateStart && !$dateEnd && !$title && !$location) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.date_added >= ?" : "WHERE b.date_added >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.date_added <= ?" : "WHERE b.date_added <= ?";
            $params[] = $dateEnd;
        }
        
        if ($title) {
            $whereClause .= $whereClause ? " AND (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)" :
                                          "WHERE (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)";
            $titleParam = "%$title%";
            $params[] = $titleParam;
            $params[] = $titleParam;
            $params[] = $titleParam;
        }
        
        if ($location) {
            $whereClause .= $whereClause ? " AND b.shelf_location LIKE ?" :
                                          "WHERE b.shelf_location LIKE ?";
            $locationParam = "%$location%";
            $params[] = $locationParam;
        }
        
        // Query to get book data with filters - FIXED with correct column names
        $query = "SELECT b.id, b.accession, b.title, 
                         p.publisher as publisher, YEAR(b.date_added) as publication_year, b.isbn, 
                         b.subject_category, b.subject_detail, b.shelf_location, b.status, b.date_added,
                         CONCAT(a.firstname, ' ', a.lastname) AS added_by, a.role,
                         GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author
                  FROM books b
                  LEFT JOIN admins a ON b.entered_by = a.id
                  LEFT JOIN publications pub ON b.id = pub.book_id
                  LEFT JOIN publishers p ON pub.publisher_id = p.id
                  LEFT JOIN contributors c ON b.id = c.book_id
                  LEFT JOIN writers w ON c.writer_id = w.id
                  $whereClause
                  GROUP BY b.id, b.accession, b.title, p.publisher, b.date_added, b.isbn, 
                           b.subject_category, b.subject_detail, b.shelf_location, b.status, a.firstname, a.lastname, a.role
                  ORDER BY b.date_added DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Accession</th>
                            <th align="center">Title</th>
                            <th align="center">Author</th>
                            <th align="center">ISBN</th>
                            <th align="center">Category</th>
                            <th align="center">Location</th>
                            <th align="center">Status</th>
                            <th align="center">Added</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Available': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Borrowed': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Reserved': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Format location - use the correct column
                $location = $row['shelf_location'];
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['accession'] . '</td>
                            <td>' . htmlspecialchars($row['title']) . '</td>
                            <td>' . htmlspecialchars($row['author'] ?: 'Not specified') . '</td>
                            <td>' . $row['isbn'] . '</td>
                            <td>' . $row['subject_category'] . '</td>
                            <td>' . htmlspecialchars($location ?: 'Not specified') . '</td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No book records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="9" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($user) $filterSummary .= "<li>User: $user</li>";
        if ($book) $filterSummary .= "<li>Book: $book</li>";
        if (!$status && !$dateStart && !$dateEnd && !$user && !$book) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($role) $filterSummary .= "<li>User Type: $role</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($search) $filterSummary .= "<li>Search Term: $search</li>";
        if ($status !== '') $filterSummary .= "<li>Status: " . ($status == '1' ? 'Active' : 'Inactive') . "</li>";
        if (!$role && !$dateStart && !$dateEnd && !$search && $status === '') {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td>' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;

case 'books':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($title) $filterSummary .= "<li>Title/Accession: $title</li>";
        if ($location) $filterSummary .= "<li>Location: $location</li>";
        if (!$status && !$dateStart && !$dateEnd && !$title && !$location) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.date_added >= ?" : "WHERE b.date_added >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.date_added <= ?" : "WHERE b.date_added <= ?";
            $params[] = $dateEnd;
        }
        
        if ($title) {
            $whereClause .= $whereClause ? " AND (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)" :
                                          "WHERE (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)";
            $titleParam = "%$title%";
            $params[] = $titleParam;
            $params[] = $titleParam;
            $params[] = $titleParam;
        }
        
        if ($location) {
            $whereClause .= $whereClause ? " AND b.shelf_location LIKE ?" :
                                          "WHERE b.shelf_location LIKE ?";
            $locationParam = "%$location%";
            $params[] = $locationParam;
        }
        
        // Query to get book data with filters - FIXED with correct column names
        $query = "SELECT b.id, b.accession, b.title, 
                         p.publisher as publisher, YEAR(b.date_added) as publication_year, b.isbn, 
                         b.subject_category, b.subject_detail, b.shelf_location, b.status, b.date_added,
                         CONCAT(a.firstname, ' ', a.lastname) AS added_by, a.role,
                         GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author
                  FROM books b
                  LEFT JOIN admins a ON b.entered_by = a.id
                  LEFT JOIN publications pub ON b.id = pub.book_id
                  LEFT JOIN publishers p ON pub.publisher_id = p.id
                  LEFT JOIN contributors c ON b.id = c.book_id
                  LEFT JOIN writers w ON c.writer_id = w.id
                  $whereClause
                  GROUP BY b.id, b.accession, b.title, p.publisher, b.date_added, b.isbn, 
                           b.subject_category, b.subject_detail, b.shelf_location, b.status, a.firstname, a.lastname, a.role
                  ORDER BY b.date_added DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Accession</th>
                            <th align="center">Title</th>
                            <th align="center">Author</th>
                            <th align="center">ISBN</th>
                            <th align="center">Category</th>
                            <th align="center">Location</th>
                            <th align="center">Status</th>
                            <th align="center">Added</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Available': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Borrowed': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Reserved': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Format location - use the correct column
                $location = $row['shelf_location'];
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['accession'] . '</td>
                            <td>' . htmlspecialchars($row['title']) . '</td>
                            <td>' . htmlspecialchars($row['author'] ?: 'Not specified') . '</td>
                            <td>' . $row['isbn'] . '</td>
                            <td>' . $row['subject_category'] . '</td>
                            <td>' . htmlspecialchars($location ?: 'Not specified') . '</td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No book records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="9" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($user) $filterSummary .= "<li>User: $user</li>";
        if ($book) $filterSummary .= "<li>Book: $book</li>";
        if (!$status && !$dateStart && !$dateEnd && !$user && !$book) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($role) $filterSummary .= "<li>User Type: $role</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($search) $filterSummary .= "<li>Search Term: $search</li>";
        if ($status !== '') $filterSummary .= "<li>Status: " . ($status == '1' ? 'Active' : 'Inactive') . "</li>";
        if (!$role && !$dateStart && !$dateEnd && !$search && $status === '') {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td>' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;

    case 'books':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($title) $filterSummary .= "<li>Title/Accession: $title</li>";
        if ($location) $filterSummary .= "<li>Location: $location</li>";
        if (!$status && !$dateStart && !$dateEnd && !$title && !$location) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.date_added >= ?" : "WHERE b.date_added >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.date_added <= ?" : "WHERE b.date_added <= ?";
            $params[] = $dateEnd;
        }
        
        if ($title) {
            $whereClause .= $whereClause ? " AND (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)" :
                                          "WHERE (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)";
            $titleParam = "%$title%";
            $params[] = $titleParam;
            $params[] = $titleParam;
            $params[] = $titleParam;
        }
        
        if ($location) {
            $whereClause .= $whereClause ? " AND b.shelf_location LIKE ?" :
                                          "WHERE b.shelf_location LIKE ?";
            $locationParam = "%$location%";
            $params[] = $locationParam;
        }
        
        // Query to get book data with filters - FIXED with correct column names
        $query = "SELECT b.id, b.accession, b.title, 
                         p.publisher as publisher, YEAR(b.date_added) as publication_year, b.isbn, 
                         b.subject_category, b.subject_detail, b.shelf_location, b.status, b.date_added,
                         CONCAT(a.firstname, ' ', a.lastname) AS added_by, a.role,
                         GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author
                  FROM books b
                  LEFT JOIN admins a ON b.entered_by = a.id
                  LEFT JOIN publications pub ON b.id = pub.book_id
                  LEFT JOIN publishers p ON pub.publisher_id = p.id
                  LEFT JOIN contributors c ON b.id = c.book_id
                  LEFT JOIN writers w ON c.writer_id = w.id
                  $whereClause
                  GROUP BY b.id, b.accession, b.title, p.publisher, b.date_added, b.isbn, 
                           b.subject_category, b.subject_detail, b.shelf_location, b.status, a.firstname, a.lastname, a.role
                  ORDER BY b.date_added DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Accession</th>
                            <th align="center">Title</th>
                            <th align="center">Author</th>
                            <th align="center">ISBN</th>
                            <th align="center">Category</th>
                            <th align="center">Location</th>
                            <th align="center">Status</th>
                            <th align="center">Added</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Available': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Borrowed': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Reserved': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Format location - use the correct column
                $location = $row['shelf_location'];
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['accession'] . '</td>
                            <td>' . htmlspecialchars($row['title']) . '</td>
                            <td>' . htmlspecialchars($row['author'] ?: 'Not specified') . '</td>
                            <td>' . $row['isbn'] . '</td>
                            <td>' . $row['subject_category'] . '</td>
                            <td>' . htmlspecialchars($location ?: 'Not specified') . '</td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No book records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="9" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($user) $filterSummary .= "<li>User: $user</li>";
        if ($book) $filterSummary .= "<li>Book: $book</li>";
        if (!$status && !$dateStart && !$dateEnd && !$user && !$book) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($role) $filterSummary .= "<li>User Type: $role</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($search) $filterSummary .= "<li>Search Term: $search</li>";
        if ($status !== '') $filterSummary .= "<li>Status: " . ($status == '1' ? 'Active' : 'Inactive') . "</li>";
        if (!$role && !$dateStart && !$dateEnd && !$search && $status === '') {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td>' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;

    case 'fines':
        // Get filter parameters
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $userFilter = isset($_GET['user']) ? $_GET['user'] : '';
        $typeFilter = isset($_GET['fine_type']) ? $_GET['fine_type'] : ''; // Changed from 'type' to 'fine_type'
        
        // Build filter summary in a single line
        $filterParams = [];
        if ($statusFilter) $filterParams[] = "Status: $statusFilter";
        if ($dateStart) $filterParams[] = "From Date: $dateStart";
        if ($dateEnd) $filterParams[] = "To Date: $dateEnd";
        if ($userFilter) $filterParams[] = "User: $userFilter";
        if ($typeFilter) $filterParams[] = "Fine Type: $typeFilter";
        
        if (empty($filterParams)) {
            $filterSummary .= "<p>No filters applied</p>";
        } else {
            $filterSummary .= "<p>" . implode(" | ", $filterParams) . "</p>";
        }
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($statusFilter) {
            $whereClause .= $whereClause ? " AND f.status = ?" : "WHERE f.status = ?";
            $params[] = $statusFilter;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND DATE(f.date) >= ?" : "WHERE DATE(f.date) >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND DATE(f.date) <= ?" : "WHERE DATE(f.date) <= ?";
            $params[] = $dateEnd;
        }
        
        if ($userFilter) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" : 
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$userFilter%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($typeFilter) {
            $whereClause .= $whereClause ? " AND f.type = ?" : "WHERE f.type = ?";
            $params[] = $typeFilter;
        }
        
        // Query to get fines data with filters - using the correct column names
        $query = "SELECT f.id, f.type, f.amount, f.status, f.date, f.payment_date, 
                  b.issue_date, b.due_date, b.return_date, 
                  u.school_id, u.firstname, u.lastname, u.usertype,
                  bk.accession, bk.title
                  FROM fines f
                  LEFT JOIN borrowings b ON f.borrowing_id = b.id
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN books bk ON b.book_id = bk.id
                  $whereClause
                  ORDER BY f.date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <thead>
                    <tr style="background-color:#f2f2f2; font-weight:bold; text-align:center;">
                        <th>#</th>
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>Book</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Fine Date</th>
                        <th>Payment Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        $totalFines = 0;
        $paidFines = 0;
        $unpaidFines = 0;
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rowCount++;
                $userName = $row['firstname'] . ' ' . $row['lastname'];
                $bookTitle = $row['title'] ? $row['title'] : 'N/A';
                $amount = $row['amount'] ? number_format($row['amount'], 2) : '0.00';
                $totalFines += floatval($row['amount']);
                
                if ($row['status'] == 'Paid') {
                    $paidFines += floatval($row['amount']);
                } else {
                    $unpaidFines += floatval($row['amount']);
                }
                
                $rowStyle = $rowCount % 2 == 0 ? 'background-color:#f9f9f9;' : '';
                $statusStyle = $row['status'] == 'Paid' ? 'color:green;' : 'color:red;';
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td style="text-align:center;">' . $rowCount . '</td>
                            <td>' . htmlspecialchars($row['school_id']) . '</td>
                            <td>' . htmlspecialchars($userName) . '</td>
                            <td>' . htmlspecialchars($bookTitle) . ' (' . htmlspecialchars($row['accession']) . ')</td>
                            <td>' . htmlspecialchars($row['type']) . '</td>
                            <td style="text-align:right;">PHP ' . $amount . '</td>
                            <td>' . ($row['issue_date'] ? date('M d, Y', strtotime($row['issue_date'])) : 'N/A') . '</td>
                            <td>' . ($row['due_date'] ? date('M d, Y', strtotime($row['due_date'])) : 'N/A') . '</td>
                            <td>' . ($row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : 'N/A') . '</td>
                            <td>' . ($row['date'] ? date('M d, Y', strtotime($row['date'])) : 'N/A') . '</td>
                            <td>' . ($row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : 'N/A') . '</td>
                            <td style="' . $statusStyle . '">' . htmlspecialchars($row['status']) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="12" style="text-align:center;">No fine records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                </table>
                <p style="margin-top:10px;">
                    <strong>Total Records:</strong> ' . $rowCount . '<br>
                    <strong>Total Fines Amount:</strong> PHP ' . number_format($totalFines, 2) . '<br>
                    <strong>Paid Fines:</strong> PHP ' . number_format($paidFines, 2) . '<br>
                    <strong>Unpaid Fines:</strong> PHP ' . number_format($unpaidFines, 2) . '
                </p>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

        case 'library_visits':
            // Get filter parameters
            $status = isset($_GET['vstatus']) ? $_GET['vstatus'] : '';
            $dateStart = isset($_GET['vdate_start']) ? $_GET['vdate_start'] : '';
            $dateEnd = isset($_GET['vdate_end']) ? $_GET['vdate_end'] : '';
            $userFilter = isset($_GET['vuser']) ? $_GET['vuser'] : '';
            $purposeFilter = isset($_GET['vpurpose']) ? $_GET['vpurpose'] : '';
            $courseFilter = isset($_GET['vcourse']) ? $_GET['vcourse'] : '';
        
            // Build filter summary in a single line
            $filterParams = [];
            if ($courseFilter) $filterParams[] = "Course: $courseFilter";
            if ($purposeFilter) $filterParams[] = "Purpose: $purposeFilter"; 
            if ($dateStart) $filterParams[] = "From Date: $dateStart";
            if ($dateEnd) $filterParams[] = "To Date: $dateEnd";
            if ($userFilter) $filterParams[] = "User: $userFilter";
        
            if (empty($filterParams)) {
                $filterSummary .= "<p>No filters applied</p>";
            } else {
                $filterSummary .= "<p>" . implode(" | ", $filterParams) . "</p>";
            }
        
            // Build WHERE clause based on filters
            $whereClause = "WHERE lv.purpose != 'Exit'"; // Exclude Exit records
            $params = [];
        
            if ($courseFilter) {
                $whereClause .= " AND u.department = ?";
                $params[] = $courseFilter;
            }
        
            if ($purposeFilter) {
                $whereClause .= " AND lv.purpose = ?";
                $params[] = $purposeFilter;
            }
        
            if ($dateStart) {
                $whereClause .= " AND DATE(lv.time) >= ?";
                $params[] = $dateStart;
            }
        
            if ($dateEnd) {
                $whereClause .= " AND DATE(lv.time) <= ?";
                $params[] = $dateEnd;
            }
        
            if ($userFilter) {
                $whereClause .= " AND (lv.student_number LIKE ? OR CONCAT(u.firstname, ' ', u.lastname) LIKE ?)";
                $userParam = "%$userFilter%";
                $params[] = $userParam;
                $params[] = $userParam;
            }
        
            // Query to get library visits with user details
            $query = "SELECT lv.id, lv.student_number, lv.time AS time_entry, lv.purpose,
                             u.firstname, u.lastname, u.department,
                             (SELECT time FROM library_visits 
                              WHERE student_number = lv.student_number 
                              AND purpose = 'Exit' 
                              AND time > lv.time 
                              ORDER BY time ASC LIMIT 1) AS time_exit
                      FROM library_visits lv
                      LEFT JOIN users u ON lv.student_number = u.school_id
                      $whereClause
                      ORDER BY lv.time DESC";
        
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
        
            // Add filter summary to PDF
            $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
            $pdf->Ln(5);
        
            // Set up the table header
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Student/Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Course/Department</th>
                                <th align="center">Purpose</th>
                                <th align="center">Time In</th>
                                <th align="center">Time Out</th>
                                <th align="center">Duration</th>
                            </tr>
                        </thead>
                        <tbody>';
        
            // Fill the table with data
            $rowCount = 0;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Calculate duration if exit time exists
                    $duration = '-';
                    if ($row['time_exit']) {
                        $timeIn = new DateTime($row['time_entry']);
                        $timeOut = new DateTime($row['time_exit']);
                        $interval = $timeIn->diff($timeOut);
                        $duration = $interval->format('%H:%I:%S');
                    }
        
                    // Add row background alternating colors
                    $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                    $rowCount++;
        
                    $html .= '<tr style="' . $rowStyle . '">
                                <td>' . $row['id'] . '</td>
                                <td>' . $row['student_number'] . '</td>
                                <td>' . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . '</td>
                                <td>' . htmlspecialchars($row['department']) . '</td>
                                <td>' . $row['purpose'] . '</td>
                                <td>' . date('M d, Y h:i A', strtotime($row['time_entry'])) . '</td>
                                <td>' . ($row['time_exit'] ? date('M d, Y h:i A', strtotime($row['time_exit'])) : '-') . '</td>
                                <td>' . $duration . '</td>
                             </tr>';
                }
            } else {
                $html .= '<tr><td colspan="8" align="center">No library visit records found</td></tr>';
            }
        
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="8" align="right">
                                  <strong>Total Records: ' . $result->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
        
            // Add table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            
            $stmt->close();
        break;
        // Get filter parameters
    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($user) $filterSummary .= "<li>User: $user</li>";
        if ($book) $filterSummary .= "<li>Book: $book</li>";
        if (!$status && !$dateStart && !$dateEnd && !$user && !$book) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($role) $filterSummary .= "<li>User Type: $role</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($search) $filterSummary .= "<li>Search Term: $search</li>";
        if ($status !== '') $filterSummary .= "<li>Status: " . ($status == '1' ? 'Active' : 'Inactive') . "</li>";
        if (!$role && !$dateStart && !$dateEnd && !$search && $status === '') {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td>' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;

case 'books':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($title) $filterSummary .= "<li>Title/Accession: $title</li>";
        if ($location) $filterSummary .= "<li>Location: $location</li>";
        if (!$status && !$dateStart && !$dateEnd && !$title && !$location) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.date_added >= ?" : "WHERE b.date_added >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.date_added <= ?" : "WHERE b.date_added <= ?";
            $params[] = $dateEnd;
        }
        
        if ($title) {
            $whereClause .= $whereClause ? " AND (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)" :
                                          "WHERE (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)";
            $titleParam = "%$title%";
            $params[] = $titleParam;
            $params[] = $titleParam;
            $params[] = $titleParam;
        }
        
        if ($location) {
            $whereClause .= $whereClause ? " AND b.shelf_location LIKE ?" :
                                          "WHERE b.shelf_location LIKE ?";
            $locationParam = "%$location%";
            $params[] = $locationParam;
        }
        
        // Query to get book data with filters - FIXED with correct column names
        $query = "SELECT b.id, b.accession, b.title, 
                         p.publisher as publisher, YEAR(b.date_added) as publication_year, b.isbn, 
                         b.subject_category, b.subject_detail, b.shelf_location, b.status, b.date_added,
                         CONCAT(a.firstname, ' ', a.lastname) AS added_by, a.role,
                         GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author
                  FROM books b
                  LEFT JOIN admins a ON b.entered_by = a.id
                  LEFT JOIN publications pub ON b.id = pub.book_id
                  LEFT JOIN publishers p ON pub.publisher_id = p.id
                  LEFT JOIN contributors c ON b.id = c.book_id
                  LEFT JOIN writers w ON c.writer_id = w.id
                  $whereClause
                  GROUP BY b.id, b.accession, b.title, p.publisher, b.date_added, b.isbn, 
                           b.subject_category, b.subject_detail, b.shelf_location, b.status, a.firstname, a.lastname, a.role
                  ORDER BY b.date_added DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Accession</th>
                            <th align="center">Title</th>
                            <th align="center">Author</th>
                            <th align="center">ISBN</th>
                            <th align="center">Category</th>
                            <th align="center">Location</th>
                            <th align="center">Status</th>
                            <th align="center">Added</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Available': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Borrowed': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Reserved': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Format location - use the correct column
                $location = $row['shelf_location'];
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['accession'] . '</td>
                            <td>' . htmlspecialchars($row['title']) . '</td>
                            <td>' . htmlspecialchars($row['author'] ?: 'Not specified') . '</td>
                            <td>' . $row['isbn'] . '</td>
                            <td>' . $row['subject_category'] . '</td>
                            <td>' . htmlspecialchars($location ?: 'Not specified') . '</td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No book records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="9" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($user) $filterSummary .= "<li>User: $user</li>";
        if ($book) $filterSummary .= "<li>Book: $book</li>";
        if (!$status && !$dateStart && !$dateEnd && !$user && !$book) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($role) $filterSummary .= "<li>User Type: $role</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($search) $filterSummary .= "<li>Search Term: $search</li>";
        if ($status !== '') $filterSummary .= "<li>Status: " . ($status == '1' ? 'Active' : 'Inactive') . "</li>";
        if (!$role && !$dateStart && !$dateEnd && !$search && $status === '') {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td>' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;

case 'books':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($title) $filterSummary .= "<li>Title/Accession: $title</li>";
        if ($location) $filterSummary .= "<li>Location: $location</li>";
        if (!$status && !$dateStart && !$dateEnd && !$title && !$location) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.date_added >= ?" : "WHERE b.date_added >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.date_added <= ?" : "WHERE b.date_added <= ?";
            $params[] = $dateEnd;
        }
        
        if ($title) {
            $whereClause .= $whereClause ? " AND (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)" :
                                          "WHERE (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)";
            $titleParam = "%$title%";
            $params[] = $titleParam;
            $params[] = $titleParam;
            $params[] = $titleParam;
        }
        
        if ($location) {
            $whereClause .= $whereClause ? " AND b.shelf_location LIKE ?" :
                                          "WHERE b.shelf_location LIKE ?";
            $locationParam = "%$location%";
            $params[] = $locationParam;
        }
        
        // Query to get book data with filters - FIXED with correct column names
        $query = "SELECT b.id, b.accession, b.title, 
                         p.publisher as publisher, YEAR(b.date_added) as publication_year, b.isbn, 
                         b.subject_category, b.subject_detail, b.shelf_location, b.status, b.date_added,
                         CONCAT(a.firstname, ' ', a.lastname) AS added_by, a.role,
                         GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author
                  FROM books b
                  LEFT JOIN admins a ON b.entered_by = a.id
                  LEFT JOIN publications pub ON b.id = pub.book_id
                  LEFT JOIN publishers p ON pub.publisher_id = p.id
                  LEFT JOIN contributors c ON b.id = c.book_id
                  LEFT JOIN writers w ON c.writer_id = w.id
                  $whereClause
                  GROUP BY b.id, b.accession, b.title, p.publisher, b.date_added, b.isbn, 
                           b.subject_category, b.subject_detail, b.shelf_location, b.status, a.firstname, a.lastname, a.role
                  ORDER BY b.date_added DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Accession</th>
                            <th align="center">Title</th>
                            <th align="center">Author</th>
                            <th align="center">ISBN</th>
                            <th align="center">Category</th>
                            <th align="center">Location</th>
                            <th align="center">Status</th>
                            <th align="center">Added</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Available': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Borrowed': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Reserved': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Format location - use the correct column
                $location = $row['shelf_location'];
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['accession'] . '</td>
                            <td>' . htmlspecialchars($row['title']) . '</td>
                            <td>' . htmlspecialchars($row['author'] ?: 'Not specified') . '</td>
                            <td>' . $row['isbn'] . '</td>
                            <td>' . $row['subject_category'] . '</td>
                            <td>' . htmlspecialchars($location ?: 'Not specified') . '</td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No book records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="9" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($user) $filterSummary .= "<li>User: $user</li>";
        if ($book) $filterSummary .= "<li>Book: $book</li>";
        if (!$status && !$dateStart && !$dateEnd && !$user && !$book) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($role) $filterSummary .= "<li>User Type: $role</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($search) $filterSummary .= "<li>Search Term: $search</li>";
        if ($status !== '') $filterSummary .= "<li>Status: " . ($status == '1' ? 'Active' : 'Inactive') . "</li>";
        if (!$role && !$dateStart && !$dateEnd && !$search && $status === '') {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td>' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;

case 'books':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($title) $filterSummary .= "<li>Title/Accession: $title</li>";
        if ($location) $filterSummary .= "<li>Location: $location</li>";
        if (!$status && !$dateStart && !$dateEnd && !$title && !$location) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.date_added >= ?" : "WHERE b.date_added >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.date_added <= ?" : "WHERE b.date_added <= ?";
            $params[] = $dateEnd;
        }
        
        if ($title) {
            $whereClause .= $whereClause ? " AND (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)" :
                                          "WHERE (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)";
            $titleParam = "%$title%";
            $params[] = $titleParam;
            $params[] = $titleParam;
            $params[] = $titleParam;
        }
        
        if ($location) {
            $whereClause .= $whereClause ? " AND b.shelf_location LIKE ?" :
                                          "WHERE b.shelf_location LIKE ?";
            $locationParam = "%$location%";
            $params[] = $locationParam;
        }
        
        // Query to get book data with filters - FIXED with correct column names
        $query = "SELECT b.id, b.accession, b.title, 
                         p.publisher as publisher, YEAR(b.date_added) as publication_year, b.isbn, 
                         b.subject_category, b.subject_detail, b.shelf_location, b.status, b.date_added,
                         CONCAT(a.firstname, ' ', a.lastname) AS added_by, a.role,
                         GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author
                  FROM books b
                  LEFT JOIN admins a ON b.entered_by = a.id
                  LEFT JOIN publications pub ON b.id = pub.book_id
                  LEFT JOIN publishers p ON pub.publisher_id = p.id
                  LEFT JOIN contributors c ON b.id = c.book_id
                  LEFT JOIN writers w ON c.writer_id = w.id
                  $whereClause
                  GROUP BY b.id, b.accession, b.title, p.publisher, b.date_added, b.isbn, 
                           b.subject_category, b.subject_detail, b.shelf_location, b.status, a.firstname, a.lastname, a.role
                  ORDER BY b.date_added DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Accession</th>
                            <th align="center">Title</th>
                            <th align="center">Author</th>
                            <th align="center">ISBN</th>
                            <th align="center">Category</th>
                            <th align="center">Location</th>
                            <th align="center">Status</th>
                            <th align="center">Added</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Available': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Borrowed': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Reserved': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Format location - use the correct column
                $location = $row['shelf_location'];
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['accession'] . '</td>
                            <td>' . htmlspecialchars($row['title']) . '</td>
                            <td>' . htmlspecialchars($row['author'] ?: 'Not specified') . '</td>
                            <td>' . $row['isbn'] . '</td>
                            <td>' . $row['subject_category'] . '</td>
                            <td>' . htmlspecialchars($location ?: 'Not specified') . '</td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No book records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="9" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($user) $filterSummary .= "<li>User: $user</li>";
        if ($book) $filterSummary .= "<li>Book: $book</li>";
        if (!$status && !$dateStart && !$dateEnd && !$user && !$book) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($role) $filterSummary .= "<li>User Type: $role</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($search) $filterSummary .= "<li>Search Term: $search</li>";
        if ($status !== '') $filterSummary .= "<li>Status: " . ($status == '1' ? 'Active' : 'Inactive') . "</li>";
        if (!$role && !$dateStart && !$dateEnd && !$search && $status === '') {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td>' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;

    case 'books':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($title) $filterSummary .= "<li>Title/Accession: $title</li>";
        if ($location) $filterSummary .= "<li>Location: $location</li>";
        if (!$status && !$dateStart && !$dateEnd && !$title && !$location) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND b.status = ?" : "WHERE b.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND b.date_added >= ?" : "WHERE b.date_added >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND b.date_added <= ?" : "WHERE b.date_added <= ?";
            $params[] = $dateEnd;
        }
        
        if ($title) {
            $whereClause .= $whereClause ? " AND (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)" :
                                          "WHERE (b.title LIKE ? OR b.accession LIKE ? OR b.isbn LIKE ?)";
            $titleParam = "%$title%";
            $params[] = $titleParam;
            $params[] = $titleParam;
            $params[] = $titleParam;
        }
        
        if ($location) {
            $whereClause .= $whereClause ? " AND b.shelf_location LIKE ?" :
                                          "WHERE b.shelf_location LIKE ?";
            $locationParam = "%$location%";
            $params[] = $locationParam;
        }
        
        // Query to get book data with filters - FIXED with correct column names
        $query = "SELECT b.id, b.accession, b.title, 
                         p.publisher as publisher, YEAR(b.date_added) as publication_year, b.isbn, 
                         b.subject_category, b.subject_detail, b.shelf_location, b.status, b.date_added,
                         CONCAT(a.firstname, ' ', a.lastname) AS added_by, a.role,
                         GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author
                  FROM books b
                  LEFT JOIN admins a ON b.entered_by = a.id
                  LEFT JOIN publications pub ON b.id = pub.book_id
                  LEFT JOIN publishers p ON pub.publisher_id = p.id
                  LEFT JOIN contributors c ON b.id = c.book_id
                  LEFT JOIN writers w ON c.writer_id = w.id
                  $whereClause
                  GROUP BY b.id, b.accession, b.title, p.publisher, b.date_added, b.isbn, 
                           b.subject_category, b.subject_detail, b.shelf_location, b.status, a.firstname, a.lastname, a.role
                  ORDER BY b.date_added DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">Accession</th>
                            <th align="center">Title</th>
                            <th align="center">Author</th>
                            <th align="center">ISBN</th>
                            <th align="center">Category</th>
                            <th align="center">Location</th>
                            <th align="center">Status</th>
                            <th align="center">Added</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Available': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Borrowed': $statusStyle = 'color:#4e73df;font-weight:bold;'; break;
                    case 'Reserved': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Damaged': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Lost': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Format location - use the correct column
                $location = $row['shelf_location'];
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['accession'] . '</td>
                            <td>' . htmlspecialchars($row['title']) . '</td>
                            <td>' . htmlspecialchars($row['author'] ?: 'Not specified') . '</td>
                            <td>' . $row['isbn'] . '</td>
                            <td>' . $row['subject_category'] . '</td>
                            <td>' . htmlspecialchars($location ?: 'Not specified') . '</td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No book records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="9" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'reservations':
        // Get filter parameters
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $book = isset($_GET['book']) ? $_GET['book'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($status) $filterSummary .= "<li>Status: $status</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($user) $filterSummary .= "<li>User: $user</li>";
        if ($book) $filterSummary .= "<li>Book: $book</li>";
        if (!$status && !$dateStart && !$dateEnd && !$user && !$book) {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Build WHERE clause based on filters
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause .= $whereClause ? " AND r.status = ?" : "WHERE r.status = ?";
            $params[] = $status;
        }
        
        if ($dateStart) {
            $whereClause .= $whereClause ? " AND r.reserve_date >= ?" : "WHERE r.reserve_date >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $whereClause .= $whereClause ? " AND r.reserve_date <= ?" : "WHERE r.reserve_date <= ?";
            $params[] = $dateEnd;
        }
        
        if ($user) {
            $whereClause .= $whereClause ? " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)" :
                                          "WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.school_id LIKE ?)";
            $userParam = "%$user%";
            $params[] = $userParam;
            $params[] = $userParam;
            $params[] = $userParam;
        }
        
        if ($book) {
            $whereClause .= $whereClause ? " AND (bk.title LIKE ? OR bk.accession LIKE ?)" :
                                          "WHERE (bk.title LIKE ? OR bk.accession LIKE ?)";
            $bookParam = "%$book%";
            $params[] = $bookParam;
            $params[] = $bookParam;
        }
        
        // Query to get reservation data with filters
        $query = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date, 
                         u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS user_name, u.usertype,
                         bk.accession, bk.title,
                         CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by, a1.role as ready_role, 
                         CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by, a2.role as issued_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $whereClause
                  ORDER BY r.reserve_date DESC";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Set up the table header without fixed widths
        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                            <th align="center">ID</th>
                            <th align="center">User</th>
                            <th align="center">Book</th>
                            <th align="center">Status</th>
                            <th align="center">Reserved On</th>
                            <th align="center">Ready On</th>
                            <th align="center">Received On</th>
                            <th align="center">Staff</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Fill the table with data
        $rowCount = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format status with color
                $status = $row['status'];
                $statusStyle = '';
                
                switch ($status) {
                    case 'Pending': $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 'Ready': $statusStyle = 'color:#36b9cc;font-weight:bold;'; break;
                    case 'Recieved': $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 'Cancelled': $statusStyle = 'color:#e74a3b;font-weight:bold;'; break;
                    default: $statusStyle = 'color:#858796;font-weight:bold;';
                }
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . htmlspecialchars($row['user_name']) . '<br><small style="color:#858796;">' . $row['school_id'] . '</small><br><small style="color:#858796;">' . $row['usertype'] . '</small></td>
                            <td>' . htmlspecialchars($row['title']) . '<br><small style="color:#858796;">Accession: ' . $row['accession'] . '</small></td>
                            <td style="' . $statusStyle . '">' . $status . '</td>
                            <td>' . date('M d, Y', strtotime($row['reserve_date'])) . '</td>
                            <td>' . ($row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-') . '</td>
                            <td>' . ($row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-') . '</td>
                            <td>' . ($row['ready_date'] ? htmlspecialchars($row['ready_by']) . '<br><small style="color:#858796;">' . $row['ready_role'] . '</small>' : '-') . '</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" align="center">No reservation records found</td></tr>';
        }
        
        // Add summary at the end of the table
        $html .= '</tbody>
                  <tfoot>
                      <tr style="background-color:#f8f9fc;">
                          <td colspan="8" align="right">
                              <strong>Total Records: ' . $result->num_rows . '</strong>
                          </td>
                      </tr>
                  </tfoot>
               </table>';
        
        // Add table to PDF
        $pdf->writeHTML($html, true, false, false, false, '');
        
        $stmt->close();
        break;

    case 'users':
        // Get filter parameters
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
        $dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Build filter summary
        $filterSummary .= "<ul>";
        if ($role) $filterSummary .= "<li>User Type: $role</li>";
        if ($dateStart) $filterSummary .= "<li>From Date: $dateStart</li>";
        if ($dateEnd) $filterSummary .= "<li>To Date: $dateEnd</li>";
        if ($search) $filterSummary .= "<li>Search Term: $search</li>";
        if ($status !== '') $filterSummary .= "<li>Status: " . ($status == '1' ? 'Active' : 'Inactive') . "</li>";
        if (!$role && !$dateStart && !$dateEnd && !$search && $status === '') {
            $filterSummary .= "<li>No filters applied</li>";
        }
        $filterSummary .= "</ul>";
        
        // Determine if we need to query admins, users, or both based on role
        $isAdmin = in_array($role, ['Admin', 'Librarian', 'Assistant', 'Encoder']);
        $isUser = in_array($role, ['Student', 'Faculty', 'Staff', 'Visitor']);
        
        // Query users if role is a user type or not specified
        if (!$isAdmin || !$role) {
            // Build WHERE clause for users
            $whereClause = "";
            $params = [];
            
            if ($isUser) {
                $whereClause .= $whereClause ? " AND usertype = ?" : "WHERE usertype = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR school_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get user data with filters
            $query = "SELECT id, school_id, CONCAT(firstname, ' ', lastname) AS name, email, usertype, 
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status IN ('Active', 'Overdue')) AS borrowed_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Returned') AS returned_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Damaged') AS damaged_books,
                             (SELECT COUNT(*) FROM borrowings WHERE user_id = users.id AND status = 'Lost') AS lost_books,
                             status, date_added, last_update, department
                      FROM users
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $usersResult = $stmt->get_result();
            $stmt->close();
        } else {
            $usersResult = false;
        }
        
        // Query admins if role is an admin type or not specified
        if ($isAdmin || !$role) {
            // Build WHERE clause for admins
            $whereClause = "";
            $params = [];
            
            if ($isAdmin) {
                $whereClause .= $whereClause ? " AND role = ?" : "WHERE role = ?";
                $params[] = $role;
            }
            
            if ($dateStart) {
                $whereClause .= $whereClause ? " AND date_added >= ?" : "WHERE date_added >= ?";
                $params[] = $dateStart;
            }
            
            if ($dateEnd) {
                $whereClause .= $whereClause ? " AND date_added <= ?" : "WHERE date_added <= ?";
                $params[] = $dateEnd;
            }
            
            if ($search) {
                $whereClause .= $whereClause ? " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)" :
                                              "WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status !== '') {
                $whereClause .= $whereClause ? " AND status = ?" : "WHERE status = ?";
                $params[] = $status;
            }
            
            // Query to get admin data with filters
            $query = "SELECT id, employee_id, CONCAT(firstname, ' ', lastname) AS name, email, role, 
                             status, date_added, last_update
                      FROM admins
                      $whereClause
                      ORDER BY date_added DESC";
            
            // Prepare and execute statement
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $types = str_repeat("s", count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $adminsResult = $stmt->get_result();
            $stmt->close();
        } else {
            $adminsResult = false;
        }
        
        // Add filter summary to PDF
        $pdf->writeHTMLCell(0, 0, '', '', $filterSummary, 0, 1, 0, true, '', true);
        $pdf->Ln(5);
        
        // Add admins section if applicable
        if ($adminsResult && $adminsResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Administrative Staff', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the admins table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">Employee ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">Role</th>
                                <th align="center">Status</th>
                                <th align="center">Date Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the admins table with data
            $rowCount = 0;
            while ($row = $adminsResult->fetch_assoc()) {
                // Format status
                $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                $statusStyle = $row['status'] == 1 ? 'color:#1cc88a;font-weight:bold;' : 'color:#e74a3b;font-weight:bold;';
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['employee_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['role'] . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="7" align="right">
                                  <strong>Total Administrative Staff: ' . $adminsResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add admins table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);
        }
        
        // Add users section if applicable
        if ($usersResult && $usersResult->num_rows > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Library Users', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            
            // Set up the users table header without fixed widths
            $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <thead>
                            <tr style="background-color:#4e73df;color:white;font-weight:bold;">
                                <th align="center">ID</th>
                                <th align="center">School ID</th>
                                <th align="center">Name</th>
                                <th align="center">Email</th>
                                <th align="center">User Type</th>
                                <th align="center">Department</th>
                                <th align="center">Borrowing Stats</th>
                                <th align="center">Status</th>
                                <th align="center">Added</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Fill the users table with data
            $rowCount = 0;
            while ($row = $usersResult->fetch_assoc()) {
                // Format status
                $statusText = '';
                $statusStyle = '';
                
                switch ($row['status']) {
                    case 1: $statusText = 'Active'; $statusStyle = 'color:#1cc88a;font-weight:bold;'; break;
                    case 2: $statusText = 'Banned'; $statusStyle = 'color:#f6c23e;font-weight:bold;'; break;
                    case 3: $statusText = 'Disabled'; $statusStyle = 'color:#858796;font-weight:bold;'; break;
                    default: $statusText = 'Inactive'; $statusStyle = 'color:#e74a3b;font-weight:bold;';
                }
                
                // Compile borrowing stats - each on a new line
                $borrowingStats = "Borrowed: {$row['borrowed_books']}<br>Returned: {$row['returned_books']}<br>Damaged: {$row['damaged_books']}<br>Lost: {$row['lost_books']}";
                
                // Add row background alternating colors
                $rowStyle = $rowCount % 2 === 0 ? 'background-color:#f8f9fc;' : 'background-color:#ffffff;';
                $rowCount++;
                
                $html .= '<tr style="' . $rowStyle . '">
                            <td>' . $row['id'] . '</td>
                            <td>' . $row['school_id'] . '</td>
                            <td>' . htmlspecialchars($row['name']) . '</td>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>' . $row['usertype'] . '</td>
                            <td>' . htmlspecialchars($row['department']) . '</td>
                            <td>' . $borrowingStats . '</td>
                            <td style="' . $statusStyle . '">' . $statusText . '</td>
                            <td>' . date('M d, Y', strtotime($row['date_added'])) . '</td>
                          </tr>';
            }
            
            // Add summary at the end of the table
            $html .= '</tbody>
                      <tfoot>
                          <tr style="background-color:#f8f9fc;">
                              <td colspan="9" align="right">
                                  <strong>Total Library Users: ' . $usersResult->num_rows . '</strong>
                              </td>
                          </tr>
                      </tfoot>
                   </table>';
            
            // Add users table to PDF
            $pdf->writeHTML($html, true, false, false, false, '');
        }
        
        // If no data found
        if ((!$usersResult || $usersResult->num_rows == 0) && (!$adminsResult || $adminsResult->num_rows == 0)) {
            $pdf->Cell(0, 10, 'No users found matching the selected criteria.', 0, 1, 'C');
        }
        break;
}

// Close and output PDF
ob_end_clean();
$pdf->lastPage();
$pdf->Output($filename, 'I');
?>
