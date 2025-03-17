<?php
require_once 'vendor/autoload.php'; // Assuming you have autoload for mPDF
include '../db.php';

// Fetch top borrowers
$query = "SELECT 
    CONCAT(users.firstname, ' ', users.lastname) AS name, 
    COUNT(borrowings.id) AS borrow_count,
    GROUP_CONCAT(DISTINCT books.title, ' (', 
                 (SELECT COUNT(*) FROM borrowings b2 
                  JOIN books bk ON b2.book_id = bk.id 
                  WHERE b2.user_id = borrowings.user_id AND bk.title = books.title), 
                 ')' SEPARATOR '; ') AS borrowed_books
          FROM borrowings 
          JOIN users ON borrowings.user_id = users.id 
          JOIN books ON borrowings.book_id = books.id
          GROUP BY users.id
          ORDER BY borrow_count DESC 
          LIMIT 10";

$result = mysqli_query($conn, $query);

$topBorrowers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $topBorrowers[] = $row;
}

// Create PDF
$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML('<h1>Top Borrowers</h1>');
$mpdf->WriteHTML('<table border="1" style="width:100%; border-collapse: collapse;">');
$mpdf->WriteHTML('<thead><tr><th>Name</th><th>Borrow Count</th><th>Borrowed Books (Count)</th></tr></thead>');
$mpdf->WriteHTML('<tbody>');

foreach ($topBorrowers as $borrower) {
    $mpdf->WriteHTML('<tr>
                        <td>' . $borrower['name'] . '</td>
                        <td>' . $borrower['borrow_count'] . '</td>
                        <td>' . $borrower['borrowed_books'] . '</td>
                      </tr>');
}

$mpdf->WriteHTML('</tbody></table>');
$mpdf->Output('Top_Borrowers.pdf', 'D');
?>
