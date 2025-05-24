<?php
set_time_limit(600); // Increase max execution time to 10 minutes

require 'vendor/autoload.php';
require '../db.php';

use TCPDF as TCPDF;
use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeLabel extends TCPDF {
    public function Header() {
        // Empty header
    }
    
    public function Footer() {
        // Empty footer
    }
}

// Support generating all barcodes if ?all=1 is set
if (isset($_GET['all']) && $_GET['all'] == '1') {
    $stmt = $conn->prepare("SELECT * FROM books WHERE title IS NOT NULL AND title != '' ORDER BY title, accession");
    $stmt->execute();
    $result = $stmt->get_result();
    $copies = $result->fetch_all(MYSQLI_ASSOC);
    $pdfTitle = 'all_barcodes.pdf';
} else {
    // Get book title from URL
    $bookTitle = isset($_GET['title']) ? $_GET['title'] : '';

    // Fetch all copies of the book
    $stmt = $conn->prepare("SELECT * FROM books WHERE title = ?");
    $stmt->bind_param("s", $bookTitle);
    $stmt->execute();
    $result = $stmt->get_result();
    $copies = $result->fetch_all(MYSQLI_ASSOC);
    $pdfTitle = 'barcodes.pdf';
}

// After fetching $copies, sort by accession (numeric or string-safe)
if (!empty($copies)) {
    usort($copies, function($a, $b) {
        // If accession is numeric, compare as numbers; otherwise, as strings
        if (is_numeric($a['accession']) && is_numeric($b['accession'])) {
            return intval($a['accession']) <=> intval($b['accession']);
        }
        return strcmp($a['accession'], $b['accession']);
    });
}

// Create PDF document
$pdf = new BarcodeLabel('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(0, 10, 0); // Remove left/right margin for centering
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 8);

// Initialize barcode generator with SVG renderer
$generator = new BarcodeGeneratorSVG();

// Calculate dimensions
$labelWidth = 60; // mm
$labelHeight = 30; // mm
$cols = 3;
$pageWidth = $pdf->getPageWidth();
$totalLabelWidth = $cols * $labelWidth;
$xStart = ($pageWidth - $totalLabelWidth) / 2; // Center labels horizontally
$x = $xStart;
$y = 10;
$colCount = 0;

foreach ($copies as $copy) {
    // Add library label
    $pdf->SetXY($x, $y);
    $pdf->Cell($labelWidth, 5, 'NBS COLLEGE LIBRARY', 0, 1, 'C');
    
    // Generate barcode as SVG
    $svgBarcode = $generator->getBarcode($copy['accession'], $generator::TYPE_CODE_39_CHECKSUM, 1, 30);
    
    // Create temporary file for SVG
    $tmpfile = tempnam(sys_get_temp_dir(), 'barcode');
    file_put_contents($tmpfile, $svgBarcode);
    
    // Add barcode image
    $pdf->ImageSVG($tmpfile, $x + ($labelWidth - ($labelWidth - 10)) / 2, $y + 5, $labelWidth - 10, 15);
    
    // Add text under barcode
    $pdf->SetXY($x, $y + 20);
    $pdf->Cell($labelWidth, 5, $copy['accession'] . ' - ' . $copy['call_number'], 0, 1, 'C');
    
    // Delete temporary file
    unlink($tmpfile);
    
    // Move to next position
    $colCount++;
    if ($colCount >= $cols) {
        $x = $xStart;
        $y += $labelHeight;
        $colCount = 0;
        
        // Add new page if needed
        if ($y > 250) {
            $pdf->AddPage();
            $y = 10;
        }
    } else {
        $x += $labelWidth;
    }
}

// Output PDF
$pdf->Output($pdfTitle, 'I');
