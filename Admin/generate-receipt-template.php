<?php
require('fpdf/fpdf.php');
include('../db.php');

class PDF extends FPDF {
    function CheckPageBreak($h) {
        if ($this->GetY() + $h > $this->GetPageHeight() - 20) {
            $this->AddPage();
            $this->TableHeader();
            $this->SetFont('Arial', '', 10);
        }
    }


    function TableHeader() {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(10, 6, 'ID', 1, 0, 'C');
        $this->Cell(70, 6, 'Book Name', 1, 0, 'C');
        $this->Cell(30, 6, 'Accession No', 1, 0, 'C');
        $this->Cell(30, 6, 'Date Borrowed', 1, 0, 'C');
        $this->Cell(25, 6, 'Date Due', 1, 0, 'C');
        $this->Cell(25, 6, 'Total', 1, 1, 'C');
    }

    function Row($data) {
        $bookName = $data['book_name'];
        $bookNameWidth = 70;

        $bookNameLines = $this->GetMultiCellHeight($bookName, $bookNameWidth);
        $rowHeight = max(6, $bookNameLines * 6); // Compute max row height

        $this->CheckPageBreak($rowHeight);

        $xPos = $this->GetX();
        $yPos = $this->GetY();

        $this->Cell(10, $rowHeight, $data['id'], 1, 0, 'C');

        $this->MultiCell($bookNameWidth, 6, $bookName, 1, 'L');

        $this->SetXY($xPos + $bookNameWidth + 10, $yPos);

        $this->Cell(30, $rowHeight, $data['accession_no'], 1, 0, 'R');
        $this->Cell(30, $rowHeight, $data['date_borrowed'], 1, 0, 'R');
        $this->Cell(25, $rowHeight, $data['date_due'], 1, 0, 'R');
        $this->Cell(25, $rowHeight, $data['total'], 1, 1, 'R');
    }

    function GetMultiCellHeight($text, $width) {
        $lines = ceil($this->GetStringWidth($text) / $width);
        return max(1, $lines);
    }
}

$pdf = new PDF('P', 'mm', "A4");
$pdf->AddPage();

// Logo
$pageWidth = $pdf->GetPageWidth();
$imageWidth = 60;
$xPos = ($pageWidth - $imageWidth) / 2;
$pdf->Image('C:\\xampp\\htdocs\\Library-System\\Admin\\inc\\img\\horizontal-nbs-logo.png', $xPos, 10, $imageWidth);
$pdf->Ln(30);

// Title
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(0, 10, 'Library Loan Receipt', 0, 1, 'C');
$pdf->Ln(5);

// Borrower Details
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(71, 5, 'Details', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(150, 5, 'ID Number: ' . 'User ID', 0, 0);
$pdf->Cell(18, 5, 'User Type: ', 0, 0);
$pdf->Cell(34, 5, 'User Type', 0, 1);
$pdf->Cell(150, 5, "Borrower's Name: " . "Name", 0, 0);
$pdf->Cell(10, 5, 'Date: ', 0, 0);
$pdf->Cell(34, 5, 'Date', 0, 1);
$pdf->Ln(5);

// Table Header
$pdf->TableHeader();

$pdf->SetFont('Arial', '', 10);

// Sample Data
$books = [];
for ($i = 1; $i <= 100; $i++) {
    $books[] = [
        'id' => $i,
        'book_name' => "Science Science Science Science Science Science Science Science Science Science",
        'accession_no' => '210028',
        'date_borrowed' => '3-13-2025',
        'date_due' => '3-20-2025',
        'total' => '15,000'
    ];
}

// Print Each Row
foreach ($books as $book) {
    $pdf->Row($book);
}

$pdf->Output();
?>
