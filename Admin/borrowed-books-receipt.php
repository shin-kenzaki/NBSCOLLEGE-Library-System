

<?php
require('fpdf/fpdf.php');
include('../db.php');



if (isset($_POST['school_id'])) {
    $school_id = mysqli_real_escape_string($conn, $_POST['school_id']);

    // Step 1: Get the borrower's name and usertype linked to the given school_id
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

    // Step 3: Fetch active borrowings for these users
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
        $pdf = new FPDF();
        $pdf->AddPage();

        // Add Image Icon (Centered at the Top)
        $pdf->Image('C:\xampp\htdocs\Library-System\Admin\inc\img\nbs-icon.png', 85, 10, 40);
        $pdf->Ln(35);

        // Header Title
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Library Loan Receipt', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 15);
        $pdf->Cell(71, 5, 'Details', 0, 0);
        $pdf->Cell(59, 5, '', 0, 0);
        $pdf->Cell(59, 10, '', 0, 1);


        // Borrower Info
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(145, 5, 'ID Number: '  . $school_id, 0, 0);
        $pdf->Cell(22, 5, 'User Type: ', 0, 0);
        $pdf->Cell(34, 5, $usertype, 0, 1);

        $receiptDate = date("m/d/Y");
        $pdf->Cell(145, 5, "Borrower's name: " . $borrower, 0, 0);
        $pdf->Cell(12, 5, 'Date: ', 0, 0);
        $pdf->Cell(34, 5, $receiptDate, 0, 1);
        $pdf->Ln(5);

        // Table Header
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(80, 6, 'Book Name', 1, 0, 'C');
        $pdf->Cell(40, 6, 'Accession Number', 1, 0, 'C');
        $pdf->Cell(35, 6, 'Date Borrowed', 1, 0, 'C');
        $pdf->Cell(33, 6, 'Date Due', 1, 1, 'C');

        // Table Data
        $pdf->SetFont('Arial', '', 11);
        while ($row = mysqli_fetch_assoc($result)) {
            $yPos = $pdf->GetY();

            $pdf->MultiCell(80, 6, $row['book_name'], 1, 'L');
            $multiCellHeight = $pdf->GetY() - $yPos;

            $xPos = $pdf->GetX();

            $pdf->SetXY($xPos + 80, $yPos);
            $pdf->MultiCell(40, $multiCellHeight, $row['accession'], 1, 'L');

            $pdf->SetXY($xPos + 120, $yPos);
            $pdf->MultiCell(35, $multiCellHeight, date('M d, Y', strtotime($row['date_borrowed'])), 1, 'L');

            $pdf->SetXY($xPos + 155, $yPos);
            $pdf->MultiCell(33, $multiCellHeight, date('M d, Y', strtotime($row['date_due'])), 1, 'L');

            $pdf->SetXY($xPos, $yPos + $multiCellHeight);
            $pdf->SetFont('Arial', '', 11);
        }

        // Signature Section (Correct Position)
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, '_______________________', 0, 1, 'R');
        $pdf->Cell(0, 0, 'Librarian Signature', 0, 1, 'R');

        // Extract Last Name from Full Name
        $borrowerNameParts = explode(' ', $borrower);
        $borrowerLastName = end($borrowerNameParts); // Get the last part as the Last Name
        $currentDate = date('Y-m-d'); // Format: YYYY-MM-DD

        // Generate PDF Filename
        $pdfFilename = $borrowerLastName . ' - Loan Receipt (' . $currentDate . ').pdf';


        $pdf->Output('', $pdfFilename);



    } else {
        echo "<script>alert('No active borrowing records found for this School ID.'); window.close();</script>";
        exit();
    }

}


?>
