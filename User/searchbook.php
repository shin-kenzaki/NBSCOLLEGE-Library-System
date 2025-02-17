<?php
session_start();
include '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Book</title>
    <style>
        .book-details-link {
            color: #4e73df;
            text-decoration: none;
        }
        .book-details-link:hover {
            text-decoration: underline;
        }
        .dataTables_filter input {
            width: 400px; 
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-bottom: 1rem; 
        }
        .clickable-row {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include '../user/inc/header.php'; ?>

    <!-- Main Content -->
    <div id="content" class="d-flex flex-column min-vh-100">
        <div class="container-fluid">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Search Book</h6>
                </div>
                <div class="card-body">
                    <!-- Books Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Call Number</th>
                                    <th>Total Copies</th>
                                    <th>Total In-Shelf</th>
                                    <th>Total Borrowed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Modified query to use only the books table
                                $query = "SELECT b.title, 
                                                (SELECT call_number FROM books ORDER BY id LIMIT 1) as call_number,
                                                COUNT(*) as total_copies,
                                                SUM(CASE WHEN b.status = 'inshelf' THEN 1 ELSE 0 END) as total_in_shelf,
                                                SUM(CASE WHEN b.status = 'borrowed' THEN 1 ELSE 0 END) as total_borrowed,
                                                (SELECT CONCAT(w.firstname, ' ', w.lastname) 
                                                 FROM contributors c 
                                                 JOIN writers w ON c.writer_id = w.id 
                                                 WHERE c.book_id = b.id AND c.role = 'Author' 
                                                 ORDER BY c.id LIMIT 1) as author
                                         FROM books b
                                         GROUP BY b.title
                                         ORDER BY b.title";

                                $result = $conn->query($query);

                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr class='clickable-row' data-href='view_book.php?title=" . urlencode($row['title']) . "'>
                                        <td>" . htmlspecialchars($row['title']) . "</td>
                                        <td>" . htmlspecialchars($row['author'] ?? 'N/A') . "</td>
                                        <td>" . htmlspecialchars($row['call_number']) . "</td>
                                        <td>" . htmlspecialchars($row['total_copies']) . "</td>
                                        <td>" . htmlspecialchars($row['total_in_shelf']) . "</td>
                                        <td>" . htmlspecialchars($row['total_borrowed']) . "</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../Admin/inc/footer.php' ?>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search within results..."
            },
            "pageLength": 10,
            "order": [[0, 'asc']], // Sort by title by default
            "responsive": true,
            "initComplete": function() {
                $('#dataTable_filter input').addClass('form-control form-control-sm');
            }
        });

        // Add click event listener to table rows
        $('#dataTable tbody').on('click', 'tr.clickable-row', function() {
            window.location.href = $(this).data('href');
        });
    });
    </script>
</body>
</html>
