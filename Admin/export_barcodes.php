<?php
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

// Get book title from URL
$bookTitle = isset($_GET['title']) ? $_GET['title'] : '';

// Fetch all copies of the book
$stmt = $conn->prepare("SELECT * FROM books WHERE title = ?");
$stmt->bind_param("s", $bookTitle);
$stmt->execute();
$result = $stmt->get_result();
$copies = $result->fetch_all(MYSQLI_ASSOC);

// Create PDF document
$pdf = new BarcodeLabel('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(10, 10, 10);
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
$x = 10;
$y = 10;
$colCount = 0;

foreach ($copies as $copy) {
    // Generate barcode as SVG
    $svgBarcode = $generator->getBarcode($copy['accession'], $generator::TYPE_CODE_128);
    
    // Create temporary file for SVG
    $tmpfile = tempnam(sys_get_temp_dir(), 'barcode');
    file_put_contents($tmpfile, $svgBarcode);
    
    // Add barcode image
    $pdf->ImageSVG($tmpfile, $x, $y, $labelWidth - 10, 15);
    
    // Add text under barcode
    $pdf->SetXY($x, $y + 15);
    $pdf->Cell($labelWidth - 10, 5, $copy['accession'], 0, 1, 'C');
    $pdf->SetXY($x, $y + 20);
    $pdf->Cell($labelWidth - 10, 5, $copy['call_number'], 0, 1, 'C');
    
    // Delete temporary file
    unlink($tmpfile);
    
    // Move to next position
    $colCount++;
    if ($colCount >= $cols) {
        $x = 10;
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
$pdf->Output('barcodes.pdf', 'I');
