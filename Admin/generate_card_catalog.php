<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection

// Include PhpSpreadsheet
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Function to generate card catalog Excel with traditional card format
function generateCardCatalogExcel($conn, $format = 'all') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Library Card Catalog');
    
    // Set up the header
    $sheet->setCellValue('A1', 'LIBRARY CARD CATALOG');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
    
    // Add generation date
    $sheet->setCellValue('A2', 'Generated on: ' . date('F j, Y g:i A'));
    $sheet->mergeCells('A2:F2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Where clause for filtering
    $whereClause = "WHERE b.title IS NOT NULL AND b.title != ''";
    if ($format === 'available') {
        $whereClause .= " AND b.status = 'Available'";
    }

    // Query to get books with all details
    $query = "SELECT 
        b.id,
        b.accession,
        b.title,
        b.preferred_title,
        b.call_number,
        b.copy_number,
        b.total_pages,
        b.series,
        b.volume,
        b.edition,
        b.subject_category,
        b.subject_detail,
        b.ISBN,
        b.shelf_location,
        b.status,
        b.contents,
        b.summary,
        b.supplementary_contents,
        b.language,
        b.dimension,
        p.publish_date,
        pub.publisher,
        pub.place,
        GROUP_CONCAT(
            CASE c.role
                WHEN 'Author' THEN CONCAT('AUTH: ', w.lastname, ', ', w.firstname, 
                    CASE WHEN w.middle_init IS NOT NULL AND w.middle_init != '' 
                         THEN CONCAT(' ', w.middle_init) ELSE '' END)
                WHEN 'Editor' THEN CONCAT('ED: ', w.lastname, ', ', w.firstname,
                    CASE WHEN w.middle_init IS NOT NULL AND w.middle_init != '' 
                         THEN CONCAT(' ', w.middle_init) ELSE '' END)
                WHEN 'Co-Author' THEN CONCAT('CO-AUTH: ', w.lastname, ', ', w.firstname,
                    CASE WHEN w.middle_init IS NOT NULL AND w.middle_init != '' 
                         THEN CONCAT(' ', w.middle_init) ELSE '' END)
                ELSE CONCAT(c.role, ': ', w.lastname, ', ', w.firstname,
                    CASE WHEN w.middle_init IS NOT NULL AND w.middle_init != '' 
                         THEN CONCAT(' ', w.middle_init) ELSE '' END)
            END
            ORDER BY c.role, w.lastname 
            SEPARATOR '\n'
        ) as contributors
    FROM books b
    LEFT JOIN publications p ON b.id = p.book_id
    LEFT JOIN publishers pub ON p.publisher_id = pub.id
    LEFT JOIN contributors c ON b.id = c.book_id
    LEFT JOIN writers w ON c.writer_id = w.id
    $whereClause
    GROUP BY b.id";
    
    if ($format === 'by_location') {
        $query .= " ORDER BY b.shelf_location, b.call_number, b.copy_number";
    } else {
        $query .= " ORDER BY b.call_number, b.copy_number";
    }

    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $currentRow = 4; // Start after header rows
    
    while ($book = $result->fetch_assoc()) {
        // Create card border for each book entry
        $cardStartRow = $currentRow;
        
        // Card header with call number and accession
        $sheet->setCellValue("A$currentRow", "CALL NUMBER: " . ($book['call_number'] ?: 'N/A'));
        $sheet->setCellValue("D$currentRow", "ACCESSION: " . $book['accession']);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("A$currentRow:F$currentRow")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
        $currentRow++;
        
        // Title (main entry)
        $title = $book['title'];
        if (!empty($book['preferred_title']) && $book['preferred_title'] !== $book['title']) {
            $title .= " [" . $book['preferred_title'] . "]";
        }
        $sheet->setCellValue("A$currentRow", "TITLE:");
        $sheet->setCellValue("B$currentRow", $title);
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("B$currentRow:F$currentRow")->getFont()->setBold(true)->setSize(11);
        $currentRow++;
        
        // Contributors (Authors, Editors, etc.)
        if (!empty($book['contributors'])) {
            $sheet->setCellValue("A$currentRow", "CONTRIBUTORS:");
            $sheet->setCellValue("B$currentRow", $book['contributors']);
            $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
            $sheet->getStyle("B$currentRow:F$currentRow")->getAlignment()->setWrapText(true);
            $currentRow++;
        }
        
        // Publication details
        $publication = '';
        if (!empty($book['place']) && !empty($book['publisher'])) {
            $publication = $book['place'] . ' : ' . $book['publisher'];
        } elseif (!empty($book['publisher'])) {
            $publication = $book['publisher'];
        }
        if (!empty($book['publish_date'])) {
            $publication .= ', ' . $book['publish_date'];
        }
        
        if (!empty($publication)) {
            $sheet->setCellValue("A$currentRow", "PUBLICATION:");
            $sheet->setCellValue("B$currentRow", $publication);
            $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
            $currentRow++;
        }
        
        // Physical description
        $physical = '';
        if (!empty($book['total_pages'])) {
            $physical = $book['total_pages'] . ' pages';
        }
        if (!empty($book['dimension'])) {
            $physical .= (!empty($physical) ? ' ; ' : '') . $book['dimension'];
        }
        
        if (!empty($physical)) {
            $sheet->setCellValue("A$currentRow", "PHYSICAL DESC:");
            $sheet->setCellValue("B$currentRow", $physical);
            $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
            $currentRow++;
        }
        
        // Series and edition
        if (!empty($book['series']) || !empty($book['volume']) || !empty($book['edition'])) {
            $seriesInfo = '';
            if (!empty($book['series'])) {
                $seriesInfo = $book['series'];
            }
            if (!empty($book['volume'])) {
                $seriesInfo .= (!empty($seriesInfo) ? ' ; ' : '') . 'v. ' . $book['volume'];
            }
            if (!empty($book['edition'])) {
                $seriesInfo .= (!empty($seriesInfo) ? ' ; ' : '') . $book['edition'] . ' ed.';
            }
            
            $sheet->setCellValue("A$currentRow", "SERIES/EDITION:");
            $sheet->setCellValue("B$currentRow", $seriesInfo);
            $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
            $currentRow++;
        }
        
        // Subject
        if (!empty($book['subject_category'])) {
            $subject = $book['subject_category'];
            if (!empty($book['subject_detail'])) {
                $subject .= ' -- ' . $book['subject_detail'];
            }
            $sheet->setCellValue("A$currentRow", "SUBJECT:");
            $sheet->setCellValue("B$currentRow", $subject);
            $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
            $currentRow++;
        }
        
        // ISBN
        if (!empty($book['ISBN'])) {
            $sheet->setCellValue("A$currentRow", "ISBN:");
            $sheet->setCellValue("B$currentRow", $book['ISBN']);
            $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
            $currentRow++;
        }
        
        // Language
        if (!empty($book['language'])) {
            $sheet->setCellValue("A$currentRow", "LANGUAGE:");
            $sheet->setCellValue("B$currentRow", $book['language']);
            $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
            $currentRow++;
        }
        
        // Supplementary contents
        if (!empty($book['supplementary_contents'])) {
            $sheet->setCellValue("A$currentRow", "INCLUDES:");
            $sheet->setCellValue("B$currentRow", $book['supplementary_contents']);
            $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
            $sheet->getStyle("B$currentRow:F$currentRow")->getAlignment()->setWrapText(true);
            $currentRow++;
        }
        
        // Contents note
        if (!empty($book['contents'])) {
            $sheet->setCellValue("A$currentRow", "CONTENTS:");
            $sheet->setCellValue("B$currentRow", $book['contents']);
            $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
            $sheet->getStyle("B$currentRow:F$currentRow")->getAlignment()->setWrapText(true);
            $currentRow++;
        }
        
        // Summary
        if (!empty($book['summary'])) {
            $sheet->setCellValue("A$currentRow", "SUMMARY:");
            $sheet->setCellValue("B$currentRow", $book['summary']);
            $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
            $sheet->getStyle("B$currentRow:F$currentRow")->getAlignment()->setWrapText(true);
            $currentRow++;
        }
        
        // Location and status
        $sheet->setCellValue("A$currentRow", "LOCATION:");
        $sheet->setCellValue("B$currentRow", $book['shelf_location'] ?: 'N/A');
        $sheet->setCellValue("D$currentRow", "STATUS:");
        $sheet->setCellValue("E$currentRow", $book['status']);
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
        $sheet->getStyle("D$currentRow")->getFont()->setBold(true);
        
        // Status color coding
        $statusColor = '';
        switch ($book['status']) {
            case 'Available':
                $statusColor = '90EE90'; // Light green
                break;
            case 'Borrowed':
                $statusColor = 'FFD700'; // Gold
                break;
            case 'Lost':
                $statusColor = 'FF6B6B'; // Light red
                break;
            case 'Damaged':
                $statusColor = 'FFA500'; // Orange
                break;
        }
        if ($statusColor) {
            $sheet->getStyle("E$currentRow")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusColor);
        }
        
        $currentRow++;
        
        // Copy number
        $sheet->setCellValue("A$currentRow", "COPY NUMBER:");
        $sheet->setCellValue("B$currentRow", $book['copy_number'] ?: '1');
        $sheet->getStyle("A$currentRow")->getFont()->setBold(true);
        $currentRow++;
        
        // Add border around the entire card
        $cardEndRow = $currentRow - 1;
        $sheet->getStyle("A$cardStartRow:F$cardEndRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
        $sheet->getStyle("A$cardStartRow:F$cardEndRow")->getBorders()->getInside()->setBorderStyle(Border::BORDER_THIN);
        
        // Add spacing between cards
        $currentRow += 2;
    }
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(15);
    $sheet->getColumnDimension('B')->setWidth(35);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(12);
    $sheet->getColumnDimension('E')->setWidth(12);
    $sheet->getColumnDimension('F')->setWidth(15);
    
    // Set default row height for better readability
    $sheet->getDefaultRowDimension()->setRowHeight(18);
    
    // Generate filename and download
    $filename = 'Library_Card_Catalog_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// Function to generate traditional CSV format (keep existing for compatibility)
function generateCardCatalog($conn, $format = 'all') {
    // Set headers for download
    $filename = 'Library_Card_Catalog_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    
    // Create output buffer
    $output = fopen('php://output', 'w');
    
    // Add headers row
    fputcsv($output, [
        'Call Number',
        'Author/Main Entry', 
        'Title',
        'Publisher',
        'Publication Year',
        'Pages',
        'Series',
        'Subject',
        'ISBN',
        'Accession No.',
        'Copy Number',
        'Location',
        'Status',
        'Supplementary Contents',  // Added supplementary contents header
        'Notes'
    ]);

    // Where clause for filtering
    $whereClause = "WHERE b.title IS NOT NULL AND b.title != ''";
    if ($format === 'available') {
        $whereClause .= " AND b.status = 'Available'";
    }

    // Query to get books with author and publisher info - now including supplementary_contents
    $query = "SELECT 
        b.id,
        b.accession,
        b.title,
        b.call_number,
        b.copy_number,
        b.total_pages,
        b.series,
        b.subject_category,
        b.ISBN,
        b.shelf_location,
        b.status,
        b.contents,
        b.summary,
        b.supplementary_contents,  /* Added supplementary_contents field */
        p.publish_date,
        pub.publisher,
        pub.place,
        GROUP_CONCAT(
            CONCAT(w.lastname, ', ', w.firstname, 
                CASE 
                    WHEN w.middle_init IS NOT NULL AND w.middle_init != '' 
                    THEN CONCAT(' ', w.middle_init) 
                    ELSE '' 
                END
            ) 
            ORDER BY c.role, w.lastname 
            SEPARATOR '; '
        ) as authors
    FROM books b
    LEFT JOIN publications p ON b.id = p.book_id
    LEFT JOIN publishers pub ON p.publisher_id = pub.id
    LEFT JOIN contributors c ON b.id = c.book_id
    LEFT JOIN writers w ON c.writer_id = w.id
    $whereClause
    GROUP BY b.id";
    
    if ($format === 'by_location') {
        $query .= " ORDER BY b.shelf_location, b.call_number, b.copy_number";
    } else {
        $query .= " ORDER BY b.call_number, b.copy_number";
    }

    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    // Write data rows
    while ($book = $result->fetch_assoc()) {
        // Format publication info
        $publication = '';
        if (!empty($book['place']) && !empty($book['publisher'])) {
            $publication = $book['place'] . ' : ' . $book['publisher'];
        } elseif (!empty($book['publisher'])) {
            $publication = $book['publisher'];
        }

        // Format notes (combine summary and contents if available)
        $notes = '';
        if (!empty($book['summary'])) {
            $notes = 'Summary: ' . $book['summary'];
        }
        if (!empty($book['contents'])) {
            if (!empty($notes)) $notes .= ' | ';
            $notes .= 'Contents: ' . $book['contents'];
        }

        // Write each row to CSV
        fputcsv($output, [
            $book['call_number'] ?: 'N/A',
            $book['authors'] ?: 'N/A',
            $book['title'],
            $publication,
            $book['publish_date'] ?: 'N/A',
            $book['total_pages'] ?: 'N/A',
            $book['series'] ?: 'N/A',
            $book['subject_category'] ?: 'N/A',
            $book['ISBN'] ?: 'N/A',
            $book['accession'],
            $book['copy_number'] ?: '1',
            $book['shelf_location'] ?: 'N/A',
            $book['status'] ?: 'Available',
            $book['supplementary_contents'] ?: 'N/A',  // Added supplementary contents data
            $notes
        ]);
    }
    
    fclose($output);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $format = $_POST['catalog_format'] ?? 'all';
    $output_format = $_POST['output_format'] ?? 'excel';
    
    try {
        if ($output_format === 'excel') {
            generateCardCatalogExcel($conn, $format);
        } else {
            generateCardCatalog($conn, $format);
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error generating card catalog: ' . $e->getMessage();
        header('Location: generate_card_catalog.php');
        exit();
    }
}

// Get statistics for display
$statsQuery = "SELECT 
    COUNT(*) as total_books,
    COUNT(DISTINCT title) as unique_titles,
    COUNT(DISTINCT call_number) as unique_call_numbers
FROM books 
WHERE title IS NOT NULL AND title != ''";

$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Card Catalog</title>
    <style>
        .card-catalog-preview {
            border: 2px solid #333;
            padding: 15px;
            margin: 20px 0;
            background-color: #fff;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .format-example {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #4e73df;
            margin: 15px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .catalog-entry {
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .catalog-field {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
    </style>
</head>
<body>
    <?php include '../admin/inc/header.php'; ?>

    <div id="content" class="d-flex flex-column min-vh-100">
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Generate Card Catalog</h1>
                <a href="book_list.php" class="btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Book List
                </a>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_books']); ?></div>
                    <div class="stat-label">Total Book Copies</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['unique_titles']); ?></div>
                    <div class="stat-label">Unique Titles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['unique_call_numbers']); ?></div>
                    <div class="stat-label">Unique Call Numbers</div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-file-excel"></i> Generate Card Catalog
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group mb-3">
                                    <label for="output_format" class="form-label">Output Format:</label>
                                    <select class="form-control" id="output_format" name="output_format">
                                        <option value="excel">Excel Format (.xlsx) - Traditional Card Layout</option>
                                        <option value="csv">CSV Format (.csv) - Simple Data Export</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Excel format provides a traditional library card catalog appearance.
                                    </small>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="catalog_format" class="form-label">Catalog Content:</label>
                                    <select class="form-control" id="catalog_format" name="catalog_format">
                                        <option value="all">Complete Card Catalog (All Books)</option>
                                        <option value="available">Available Books Only</option>
                                        <option value="by_location">Group by Shelf Location</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Choose which books to include in your catalog export.
                                    </small>
                                </div>

                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Excel Card Catalog Features:</h6>
                                    <ul class="mb-0">
                                        <li><strong>Traditional Layout:</strong> Each book appears as a properly formatted library card</li>
                                        <li><strong>Multiple Lines:</strong> All information is organized in separate lines like real catalog cards</li>
                                        <li><strong>Status Color Coding:</strong> Available (green), Borrowed (gold), Lost (red), Damaged (orange)</li>
                                        <li><strong>Complete Information:</strong> Contributors, subjects, physical description, and notes</li>
                                        <li><strong>Professional Borders:</strong> Each card has proper borders and spacing</li>
                                        <li><strong>Print Ready:</strong> Formatted for professional library use</li>
                                    </ul>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-download"></i> Generate & Download Catalog
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-eye"></i> Traditional Card Catalog Preview
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="format-example">
                                <strong>Traditional Library Card Format:</strong>
                                <div class="card-catalog-preview">
                                    <div class="catalog-entry">
                                        <div><span class="catalog-field">CALL NUMBER:</span> FIL DG651 C38 c2007 c.1</div>
                                        <div><span class="catalog-field">ACCESSION:</span> 1245</div>
                                    </div>
                                    <div class="catalog-entry">
                                        <div><span class="catalog-field">TITLE:</span> <strong>Cebu: Pride of Place</strong></div>
                                    </div>
                                    <div class="catalog-entry">
                                        <div><span class="catalog-field">CONTRIBUTORS:</span></div>
                                        <div style="margin-left: 20px;">AUTH: Mondoñedo, E. Billy</div>
                                    </div>
                                    <div class="catalog-entry">
                                        <div><span class="catalog-field">PUBLICATION:</span> Cebu : Arts Council of Cebu Foundation, 2007</div>
                                    </div>
                                    <div class="catalog-entry">
                                        <div><span class="catalog-field">PHYSICAL DESC:</span> xiii 564 pages</div>
                                    </div>
                                    <div class="catalog-entry">
                                        <div><span class="catalog-field">SUBJECT:</span> Geographical</div>
                                    </div>
                                    <div class="catalog-entry">
                                        <div><span class="catalog-field">ISBN:</span> 9789719396109</div>
                                    </div>
                                    <div class="catalog-entry">
                                        <div><span class="catalog-field">INCLUDES:</span> Appendix, Bibliography, and Index</div>
                                    </div>
                                    <div class="catalog-entry">
                                        <div><span class="catalog-field">LOCATION:</span> FIL</div>
                                        <div><span class="catalog-field">STATUS:</span> Available</div>
                                    </div>
                                    <div class="catalog-entry">
                                        <div><span class="catalog-field">COPY NUMBER:</span> 1</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-success">
                                <small><i class="fas fa-lightbulb"></i> 
                                <strong>Professional Format:</strong> The Excel output follows traditional library cataloging standards with proper formatting, borders, and color coding for easy reference.</small>
                            </div>

                            <div class="alert alert-warning">
                                <small><i class="fas fa-print"></i> 
                                <strong>Print Tip:</strong> For printing, set your Excel to landscape orientation and adjust margins for best results.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../Admin/inc/footer.php' ?>

    <script>
        // Add loading state when form is submitted
        $('form').on('submit', function() {
            var $submitBtn = $('button[type="submit"]');
            var format = $('#output_format').val();
            var loadingText = format === 'excel' ? 
                '<i class="fas fa-spinner fa-spin"></i> Generating Excel Catalog...' : 
                '<i class="fas fa-spinner fa-spin"></i> Generating CSV...';
            
            $submitBtn.html(loadingText);
            $submitBtn.prop('disabled', true);
            
            // Reset button after a delay
            setTimeout(function() {
                $submitBtn.html('<i class="fas fa-download"></i> Generate & Download Catalog');
                $submitBtn.prop('disabled', false);
            }, 3000);
        });

        // Update preview based on selected format
        $('#output_format').on('change', function() {
            var format = $(this).val();
            if (format === 'excel') {
                $('.alert-info h6').html('<i class="fas fa-info-circle"></i> Excel Card Catalog Features:');
            } else {
                $('.alert-info h6').html('<i class="fas fa-info-circle"></i> CSV Export Information:');
            }
        });
    </script>
</body>
</html>
