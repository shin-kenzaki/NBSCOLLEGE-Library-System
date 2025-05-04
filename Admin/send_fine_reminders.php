<?php
session_start();
include('../db.php');
require 'mailer.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the response header to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'remind_all') {
        try {
            // Fetch all unpaid fines where reminder_sent = 0
            $query = "SELECT f.id, f.amount, f.date AS fine_date, b.due_date, b.issue_date, b.return_date,
                    bk.accession, bk.title AS book_title,
                    CONCAT(u.firstname, ' ', u.lastname) AS borrower_name, u.email, f.status, f.reminder_sent
            FROM fines f
            JOIN borrowings b ON f.borrowing_id = b.id
            JOIN books bk ON b.book_id = bk.id
            JOIN users u ON b.user_id = u.id
            WHERE f.status = 'Unpaid' AND f.reminder_sent = 0";
                    $result = $conn->query($query);

            if (!$result) {
                throw new Exception("Database query failed: " . $conn->error);
            }

            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'No unpaid fines to remind.']);
                exit;
            }

            $borrowerFines = [];
            while ($fine = $result->fetch_assoc()) {
                $email = $fine['email'];

                if (!isset($borrowerFines[$email])) {
                    $borrowerFines[$email] = [
                        'borrower_name' => $fine['borrower_name'],
                        'email' => $email,
                        'fines' => []
                    ];
                }

                $borrowerFines[$email]['fines'][] = [
                    'id' => $fine['id'],
                    'accession' => $fine['accession'],
                    'title' => $fine['book_title'],
                    'borrowed_date' => date('F d, Y', strtotime($fine['issue_date'])), // Use issue_date here
                    'due_date' => date('F d, Y', strtotime($fine['due_date'])),
                    'returned_date' => $fine['return_date'] ? date('F d, Y', strtotime($fine['return_date'])) : 'Not Returned',
                    'fine_amount' => floatval($fine['amount'])
                ];
            }

            $successCount = 0;
            $errorCount = 0;

            // Send emails to each borrower
            foreach ($borrowerFines as $borrowerData) {
                $borrowerName = htmlspecialchars($borrowerData['borrower_name']);
                $email = htmlspecialchars($borrowerData['email']);
                $fines = $borrowerData['fines'];

                // Prepare the table rows and calculate the total fine amount
                $tableRows = '';
                $totalFineAmount = 0;
                $fineIds = [];
                foreach ($fines as $fine) {
                    $tableRows .= "
                    <tr>
                        <td>{$fine['accession']}</td>
                        <td>{$fine['title']}</td>
                        <td>{$fine['borrowed_date']}</td> <!-- This now uses issue_date -->
                        <td>{$fine['due_date']}</td>
                        <td>{$fine['returned_date']}</td>
                        <td>₱" . number_format($fine['fine_amount'], 2) . "</td>
                    </tr>";
                    $totalFineAmount += $fine['fine_amount'];
                    $fineIds[] = $fine['id'];
                }

                // Add a row for the total fine amount
                $tableRows .= "
                    <tr>
                        <td colspan='5' style='text-align: right; font-weight: bold;'>Total Fine Amount:</td>
                        <td style='font-weight: bold;'>₱" . number_format($totalFineAmount, 2) . "</td>
                    </tr>";

                // Prepare email content
                $subject = 'Library Fine Reminder';
                $body = "
                    <p>Dear $borrowerName,</p>
                    <p>This is a courteous reminder that you currently have unpaid library fines associated with the materials listed below. We kindly request that you settle these fines at your earliest convenience to ensure uninterrupted access to library services and to avoid any potential delays in your clearance process.</p>
                    <p><strong>Details of Unpaid Fines:</strong></p>
                    <table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
                        <thead>
                            <tr>
                                <th>Accession No</th>
                                <th>Title of Book</th>
                                <th>Borrowed Date</th>
                                <th>Due Date</th>
                                <th>Returned Date</th>
                                <th>Fine Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            $tableRows
                        </tbody>
                    </table>
                    <p>To settle your fines, please visit the library or contact us for further assistance.</p>
                    <p>If you have any questions or need assistance, please do not hesitate to contact us.</p>
                    <p>Thank you for your attention to this matter.</p>
                    <p><strong>NBS College Library</strong><br>
                    3rd & 4th Floor National Book Store Building<br>
                    Scout Borromeo cor. Quezon Avenue<br>
                    Diliman, Quezon City 1113<br>
                    Tel No. (02) 7007 4647</p>
                ";

                // Send email
                $mail = require 'mailer.php';
                try {
                    $mail->setFrom('noreply@nbs-library-system.com', 'NBS College Library');
                    $mail->addReplyTo('noreply@nbs-library-system.com', 'NBS College Library'); // Add NoReply address
                    $mail->addAddress($email, $borrowerName);
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    $mail->isHTML(true);

                    if ($mail->send()) {
                        $successCount++;

                        // Update reminder_sent to 1 for the reminded fines
                        $updateQuery = "UPDATE fines SET reminder_sent = 1 WHERE id = ?";
                        $updateStmt = $conn->prepare($updateQuery);
                        foreach ($fineIds as $id) {
                            $updateStmt->bind_param('i', $id);
                            $updateStmt->execute();
                        }
                    } else {
                        $errorCount++;
                    }
                } catch (Exception $e) {
                    $errorCount++;
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => "$successCount reminders sent successfully. $errorCount failed."
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
exit;
?>