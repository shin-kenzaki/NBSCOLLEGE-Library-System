

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

    $user_ids_query = "SELECT id FROM users WHERE school_id = '$school_id'";
    $user_ids_result = mysqli_query($conn, $user_ids_query);

    $user_ids = [];
    while ($user_row = mysqli_fetch_assoc($user_ids_result)) {
        $user_ids[] = $user_row['id'];
    }

    $user_ids_str = implode(',', $user_ids);

    $sql = "SELECT bk.title AS book_name,
                   bk.accession,
                   b.issue_date AS date_borrowed,
                   b.due_date AS date_due
            FROM borrowings b
            JOIN books bk ON b.book_id = bk.id
            WHERE b.user_id IN ($user_ids_str)
            AND b.status = 'Active'";



    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {


        $pdf = new PDF('P', 'mm', 'A4');
        $pdf->AddPage();

        $pageWidth = $pdf->GetPageWidth();
        $imageWidth = 60;
        $xPos = ($pageWidth - $imageWidth) / 2;
        $pdf->Image('C:\\xampp\\htdocs\\Library-System\\Admin\\inc\\img\\horizontal-nbs-logo.png', $xPos, 10, $imageWidth);
        $pdf->Ln(35);

        // Header Title
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->Cell(0, 10, 'Library Loan Receipt', 0, 1, 'C');
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
        $pdf->Cell(80, 6, 'Book Name', 1, 0, 'C');
        $pdf->Cell(40, 6, 'Accession Number', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Date Borrowed', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Date Due', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        $counter = 1;

        while ($row = mysqli_fetch_assoc($result)) {
            $yPos = $pdf->GetY();

            $titleWidth = 80;
            $lineHeight = 6;
            $numLines = ceil($pdf->GetStringWidth($row['book_name']) / $titleWidth);
            $rowHeight = max($numLines * $lineHeight, 6);

            $pdf->CheckPageBreak($rowHeight);

            $pdf->Cell(10, $rowHeight, $counter, 1, 0, 'C');

            $xPos = $pdf->GetX();
            $pdf->MultiCell($titleWidth, $lineHeight, $row['book_name'], 1, 'L');

            $pdf->SetXY($xPos + $titleWidth, $yPos);

            $pdf->Cell(40, $rowHeight, $row['accession'], 1, 0, 'L');
            $pdf->Cell(30, $rowHeight, date('M d, Y', strtotime($row['date_borrowed'])), 1, 0, 'R');
            $pdf->Cell(30, $rowHeight, date('M d, Y', strtotime($row['date_due'])), 1, 1, 'R');

            $counter++;
        }


        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, '_______________________', 0, 1, 'R');
        $pdf->Cell(0, 0, 'Librarian Signature', 0, 1, 'R');

        $borrowerLastName = explode(' ', $borrower);
        $pdfFilename = end($borrowerLastName) . ' - Loan Receipt (' . date('Y-m-d') . ').pdf';

        $pdf->Output('', $pdfFilename);

    } else {
        echo "<script>alert('No active borrowing records found for this School ID.'); window.close();</script>";
        exit();
    }

}

?>
