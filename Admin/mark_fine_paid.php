<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include('../db.php');
require('fpdf/fpdf.php'); // Include FPDF for receipt generation

class PDF extends FPDF {
    function CheckPageBreak($h) {
        if ($this->GetY() + $h > $this->GetPageHeight() - 22) {
            $this->AddPage();
        }
    }

    function SetDash($black = false, $white = false) {
        if ($black && $white) {
            $s = sprintf('[%.3F %.3F] 0 d', $black, $white);
        } else {
            $s = '[] 0 d';
        }
        $this->_out($s);
    }

    function generateReceipt($borrower, $usertype, $school_id, $result, $copyType, $invoiceSale, $paymentDate, $isBottomHalf = false) {
        if ($isBottomHalf) {
            $this->SetY($this->GetPageHeight() / 2 + 10); // Start at the middle of the page for the second copy
        }

        $pageWidth = $this->GetPageWidth() - 40; // Adjust for left and right margins
        $imageWidth = 40;
        $xPos = ($pageWidth - $imageWidth) / 2 + 20; // Adjust for left margin
        $this->Image('C:\\xampp\\htdocs\\Library-System\\Admin\\inc\\img\\horizontal-nbs-logo.png', $xPos, $this->GetY(), $imageWidth);

        // Copy Type Header
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(20, $this->GetY()); // Position at the left margin, aligned with the image
        $this->Cell(0, 5, strtoupper($copyType), 0, 1, 'L');
        $this->Ln(15);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, 'NBS COLLEGE LIBRARY', 0, 1, 'C');
        $this->Ln(5);

        $receiptDate = date("m/d/Y");
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, 'LIBRARY FINE RECEIPT', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, 'Payment Date: ' . $paymentDate, 0, 1, 'C'); // Display the payment date
        $this->Ln(5);

        // Borrower Details
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(71, 5, 'Details', 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(130, 5, 'ID Number: ' . $school_id, 0, 0);
        $this->Cell(19, 5, 'User Type: ', 0, 0);
        $this->Cell(50, 5, $usertype, 0, 1);

        $this->Cell(130, 5, "Borrower's Name: " . $borrower, 0, 0);
        $this->SetTextColor(255, 0, 0); // RGB for red
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(24, 5, 'INVOICE No: ', 0, 0);
        $this->Cell(50, 5, $invoiceSale, 0, 1); // Add invoice number here
        $this->SetTextColor(0, 0, 0);

        $this->Ln(1);

        // Table Header
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(170, 0, '', 'T', 1, 'C'); // Top border for the table
        $this->Cell(80, 10, 'Title', 0, 0, 'L');
        $this->Cell(40, 10, 'Fine Type', 0, 0, 'L');
        $this->Cell(30, 10, 'Status', 0, 0, 'L');
        $this->Cell(30, 10, 'Amount', 0, 1, 'L');

        $this->SetFont('Arial', '', 8);

        $totalAmount = 0; // Initialize total amount

        while ($row = mysqli_fetch_assoc($result)) {
            $yPos = $this->GetY();

            $titleWidth = 80;
            $lineHeight = 3;
            $numLines = ceil($this->GetStringWidth($row['book_title']) / $titleWidth);
            $rowHeight = max($numLines * $lineHeight, 6);

            $this->CheckPageBreak($rowHeight);

            $bookTitle = strlen($row['book_title']) > 40 ? substr($row['book_title'], 0, 40) . '...' : $row['book_title'];

            $xPos = $this->GetX();
            $this->MultiCell($titleWidth, $lineHeight, $bookTitle, 0, 'L');

            $this->SetXY($xPos + $titleWidth, $yPos);

            $this->Cell(40, 3.5, $row['type'], 0, 0, 'L');
            $this->Cell(30, 3.5, $row['status'], 0, 0, 'L'); // Status goes here
            $this->Cell(30, 3.5, number_format($row['amount'], 2), 0, 1, 'L'); // Amount goes here

            // Add the amount to the total
            $totalAmount += $row['amount'];
        }
        $this->Ln(3);
        $this->Cell(170, 0, '', 'T', 1, 'C'); // Bottom border for the table

        // Display Total Amount
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(130, 6, '', 0, 0);
        $this->Cell(20, 6, 'Total:', 0, 0, 'R');
        $this->Cell(30, 6, number_format($totalAmount, 2), 0, 1, 'L');

        // Signature Section
        $this->Ln(1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, '_______________________', 0, 1, 'R');
        $this->Cell(0, 0, 'Librarian Signature', 0, 1, 'R');
    }
}


try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Check if fine_ids are provided
    if (!isset($_POST['fine_ids'])) {
        throw new Exception("Fine IDs are required");
    }

    // Decode the fine_ids JSON string
    $fineIds = json_decode($_POST['fine_ids'], true);
    if (!is_array($fineIds) || empty($fineIds)) {
        throw new Exception("Invalid or empty Fine IDs");
    }

    // Validate payment_date and invoice_sale
    $paymentDate = isset($_POST['payment_date']) ? $_POST['payment_date'] : null;
    $invoiceSale = isset($_POST['invoice_sale']) ? $_POST['invoice_sale'] : null;

    if (!$paymentDate || !$invoiceSale) {
        throw new Exception("Payment date and invoice number are required");
    }

    // Start transaction
    $conn->begin_transaction();

    // Validate all fines belong to the same borrower
    $schoolIdQuery = "SELECT DISTINCT u.school_id, CONCAT(u.firstname, ' ', u.lastname) AS borrower, u.usertype
                      FROM fines f
                      JOIN borrowings b ON f.borrowing_id = b.id
                      JOIN users u ON b.user_id = u.id
                      WHERE f.id IN (" . implode(',', array_map('intval', $fineIds)) . ")";
    $schoolIdResult = $conn->query($schoolIdQuery);

    if ($schoolIdResult->num_rows > 1) {
        throw new Exception("Cannot mark as paid for fines with different borrowers");
    }

    $borrowerData = $schoolIdResult->fetch_assoc();
    $school_id = $borrowerData['school_id'];
    $borrower = $borrowerData['borrower'];
    $usertype = $borrowerData['usertype'];

    // Update all selected fines as paid
    $updateQuery = "UPDATE fines
                    SET status = 'Paid',
                        payment_date = ?,
                        invoice_sale = ?
                    WHERE id IN (" . implode(',', array_map('intval', $fineIds)) . ") AND status = 'Unpaid'";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ss", $paymentDate, $invoiceSale);

    if ($stmt->execute()) {
        $conn->commit();

        // Generate Receipt
        $sql = "SELECT f.type, f.amount, f.status, bk.title AS book_title
                FROM fines f
                JOIN borrowings b ON f.borrowing_id = b.id
                JOIN books bk ON b.book_id = bk.id
                WHERE f.id IN (" . implode(',', array_map('intval', $fineIds)) . ")";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $pdf = new PDF('P', 'mm', 'A4');
            $pdf->SetLeftMargin(20); // Set left margin
            $pdf->SetRightMargin(20); // Set right margin
            $pdf->AddPage();

            // Generate Borrower Copy (Top Half)
            $pdf->generateReceipt($borrower, $usertype, $school_id, $result, 'Borrower Copy', $invoiceSale, $paymentDate);

            // Add a dashed line to separate the copies
            $pdf->SetDash(1, 1); // Set dashed line
            $pdf->Line(20, $pdf->GetPageHeight() / 2, $pdf->GetPageWidth() - 20, $pdf->GetPageHeight() / 2);
            $pdf->SetDash(); // Reset to solid line

            // Reset the result pointer for the second copy
            mysqli_data_seek($result, 0);

            // Generate Librarian Copy (Bottom Half)
            $pdf->generateReceipt($borrower, $usertype, $school_id, $result, 'Librarian Copy', $invoiceSale, $paymentDate, true);

            // Output the PDF
            $borrowerNameParts = explode(' ', $borrower);
            $borrowerLastName = end($borrowerNameParts); // Get the last part as the last name

            // Format today's date
            $formattedDate = date('Y-m-d', strtotime($paymentDate)); // Format: YYYY-MM-DD

            $pdfFilename = 'Fine_receipt_' . $invoiceSale . ' - ' . $borrowerLastName . '(' . $formattedDate . ').pdf';
            $pdf->Output('I', $pdfFilename); // Inline display in the browser
        } else {
            throw new Exception("No fines found for receipt generation");
        }
    } else {
        throw new Exception("Failed to update fine statuses");
    }
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>