<?php
require('fpdf/fpdf.php');
include('../db.php');

class PDF extends FPDF {
    function CheckPageBreak($h) {
        if ($this->GetY() + $h > $this->GetPageHeight() - 22) {
            $this->AddPage();
        }
    }
}

if (isset($_POST['school_id'])) {
    $school_id = mysqli_real_escape_string($conn, $_POST['school_id']);

    $user_info_query = "SELECT CONCAT(firstname, ' ', lastname) AS borrower, usertype
                        FROM users
                        WHERE school_id = '$school_id'
                        LIMIT 1";
    $user_info_result = mysqli_query($conn, $user_info_query);
    $user_info = mysqli_fetch_assoc($user_info_result);

    if (!$user_info) {
        echo "<script>alert('No user found for this School ID.'); window.close();</script>";
        exit();
    }

    $borrower = $user_info['borrower'];
    $usertype = $user_info['usertype'];

    // Step 2: Get the user IDs linked to this School ID
    $user_ids_query = "SELECT id FROM users WHERE school_id = '$school_id'";
    $user_ids_result = mysqli_query($conn, $user_ids_query);

    $user_ids = [];
    while ($user_row = mysqli_fetch_assoc($user_ids_result)) {
        $user_ids[] = $user_row['id'];
    }

    // Step 3: Fetch fines for these users
    $user_ids_str = implode(',', $user_ids);

    $sql = "SELECT f.type, f.amount, f.status, f.date AS fine_date, f.payment_date,
            bk.title AS book_title
            FROM fines f
            JOIN borrowings b ON f.borrowing_id = b.id
            JOIN books bk ON b.book_id = bk.id
            WHERE b.user_id IN ($user_ids_str) AND f.status = 'Unpaid'
            ORDER BY f.date DESC";


    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();

        $pageWidth = $pdf->GetPageWidth();
        $imageWidth = 60;
        $xPos = ($pageWidth - $imageWidth) / 2;
        $pdf->Image('C:\\xampp\\htdocs\\Library-System\\Admin\\inc\\img\\horizontal-nbs-logo.png', $xPos, 10, $imageWidth);
        $pdf->Ln(35);

        // Header Title
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Library Fine Receipt', 0, 1, 'C');
        $pdf->Ln(5);

        // Borrower Details
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->Cell(71, 5, 'Details', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(150, 5, 'ID Number: ' . $school_id, 0, 0);
        $pdf->Cell(18, 5, 'User Type: ', 0, 0);
        $pdf->Cell(34, 5, $usertype, 0, 1);

        $receiptDate = date("m/d/Y");
        $pdf->Cell(150, 5, "Borrower's Name: " . $borrower, 0, 0);
        $pdf->Cell(10, 5, 'Date: ', 0, 0);
        $pdf->Cell(34, 5, $receiptDate, 0, 1);
        $pdf->Ln(5);



        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(10, 6, 'ID', 1, 0, 'C');
        $pdf->Cell(80, 6, 'Book Title', 1, 0, 'C');
        $pdf->Cell(40, 6, 'Fine Type', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Status', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Amount', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        $counter = 1;
        $totalAmount = 0; // Initialize total amount

        while ($row = mysqli_fetch_assoc($result)) {
            $yPos = $pdf->GetY();

            $titleWidth = 80;
            $lineHeight = 6;
            $numLines = ceil($pdf->GetStringWidth($row['book_title']) / $titleWidth);
            $rowHeight = max($numLines * $lineHeight, 6);


            $pdf->Cell(10, $rowHeight, $counter, 1, 0, 'C');

            $xPos = $pdf->GetX();
            $pdf->MultiCell($titleWidth, $lineHeight, $row['book_title'], 1, 'L');

            $pdf->SetXY($xPos + $titleWidth, $yPos);

            $pdf->Cell(40, $rowHeight, $row['type'], 1,0, 'L');
            $pdf->Cell(30, $rowHeight, $row['status'], 1,0, 'L'); // Status goes here
            $pdf->Cell(30, $rowHeight, number_format($row['amount'], 2), 1, 1, 'L'); // Amount goes here



            // Add the amount to the total
            $totalAmount += $row['amount'];
            $counter++;
        }

        // Display Total Amount
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(130, 6, '', 0, 0);
        $pdf->Cell(30, 6, 'TOTAL:', 0, 0, 'R');
        $pdf->Cell(30, 6, number_format($totalAmount, 2), 1, 1, 'L');



        // Signature Section
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, '_______________________', 0, 1, 'R');
        $pdf->Cell(0, 0, 'Librarian Signature', 0, 1, 'R');

        // Extract Last Name from Full Name
        $borrowerNameParts = explode(' ', $borrower);
        $borrowerLastName = end($borrowerNameParts); // Get the last part as the Last Name
        $currentDate = date('Y-m-d'); // Format: YYYY-MM-DD



        // Generate PDF Filename
        $pdfFilename = $borrowerLastName . ' - Fine Receipt (' . $currentDate . ').pdf';

        $pdf->Output('', $pdfFilename);
    } else {
        echo "<script>alert('No fines found for this School ID.'); window.close();</script>";
        exit();
    }
}
?>
