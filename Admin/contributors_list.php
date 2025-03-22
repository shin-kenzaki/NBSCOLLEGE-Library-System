<?php
ob_start(); // Start output buffering
session_start();

// Check login and handle bulk delete first
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Handle bulk delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['ids']) && !empty($_POST['ids'])) {
        require_once '../db.php';
        $conn->begin_transaction();
        try {
            $deleteCount = 0;
            $stmt = $conn->prepare("DELETE FROM contributors WHERE id = ?");
            
            foreach ($_POST['ids'] as $id) {
                $id = (int)$id;
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $deleteCount++;
                }
            }
            
            $conn->commit();
            
            echo json_encode(['success' => true, 'message' => "$deleteCount contributor(s) deleted successfully."]);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => "Error deleting contributors: " . $e->getMessage()]);
            exit();
        }
    }
}

// Include other files after header operations
include '../admin/inc/header.php';
include '../db.php';

// Initialize selected contributors array in session if not exists
if (!isset($_SESSION['selectedContributorIds'])) {
    $_SESSION['selectedContributorIds'] = [];
}

// Get search parameters
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Query to fetch contributors data
$query = "SELECT 
            GROUP_CONCAT(c.id ORDER BY c.id) as id_ranges,
            b.title as book_title,
            CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) as writer_name,
            c.role
          FROM contributors c
          JOIN books b ON c.book_id = b.id
          JOIN writers w ON c.writer_id = w.id
          GROUP BY b.title, w.id, c.role
          ORDER BY b.title";

$result = $conn->query($query);
$contributors_data = array();

while ($row = $result->fetch_assoc()) {
    // Format ID ranges
    $ids = explode(',', $row['id_ranges']);
    $ranges = [];
    $start = $ids[0];
    $prev = $ids[0];
    
    for ($i = 1; $i < count($ids); $i++) {
        if ($ids[$i] - $prev > 1) {
            $ranges[] = $start == $prev ? $start : "$start-$prev";
            $start = $ids[$i];
        }
        $prev = $ids[$i];
    }
    $ranges[] = $start == $prev ? $start : "$start-$prev";
    
    $row['id_ranges'] = implode(', ', $ranges);
    $contributors_data[] = $row;
}
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Contributors List</h6>
                <div class="d-flex align-items-center">
                    <button id="returnSelectedBtn" class="btn btn-danger btn-sm mr-2 bulk-delete-btn" disabled>
                        <i class="fas fa-trash"></i>
                        <span>Delete Selected</span>
                        <span class="badge badge-light ml-1">0</span>
                    </button>
                </div>
            </div>
            <div class="card-body px-0">
                <div class="table-responsive px-3">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="cursor: pointer; text-align: center;" id="checkboxHeader"><input type="checkbox" id="selectAll"></th>
                                <th style='text-align: center;'>ID Range</th>
                                <th style='text-align: center;'>Book Title</th>
                                <th style='text-align: center;'>Contributor</th>
                                <th style='text-align: center;'>Role</th>
                                <th style='text-align: center;'>Total Books</th>
                            </tr>
                        </thead>
                        <tbody id="contributorsTableBody">
                            <?php foreach ($contributors_data as $row): ?>
                            <tr>
                                <td style='text-align: center;'><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($row['id_ranges']); ?>"></td>
                                <td style='text-align: center;'><?php echo htmlspecialchars($row['id_ranges']); ?></td>
                                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['writer_name']); ?></td>
                                <td style='text-align: center;'><?php echo htmlspecialchars($row['role']); ?></td>
                                <td style='text-align: center;'><?php 
                                    $total = 0;
                                    $ranges = explode(',', $row['id_ranges']);
                                    foreach ($ranges as $range) {
                                        if (strpos($range, '-') !== false) {
                                            list($start, $end) = explode('-', $range);
                                            $total += ($end - $start + 1);
                                        } else {
                                            $total += 1;
                                        }
                                    }
                                    echo $total;
                                ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for bulk delete -->
<form id="bulkActionForm" method="POST" action="contributors_list.php">
    <input type="hidden" name="action" value="delete">
    <div id="selected_ids_container"></div>
</form>

<!-- Context Menu -->
<div id="contextMenu" class="context-menu" style="display: none; position: fixed; z-index: 1000;">
    <ul class="context-menu-list list-unstyled m-0">
        <li class="context-menu-item" data-action="edit"><i class="fas fa-edit"></i> Update</li>
        <li class="context-menu-item" data-action="delete"><i class="fas fa-trash"></i> Delete</li>
    </ul>
</div>

<style>
.context-menu {
    background: #ffffff;
    border: 1px solid #cccccc;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    padding: 5px 0;
}
.context-menu-item {
    padding: 8px 15px;
    cursor: pointer;
}
.context-menu-item:hover {
    background-color: #f0f0f0;
}

/* Add responsive table styles */
.table-responsive {
    width: 100%;
    margin-bottom: 1rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

#dataTable th,
#dataTable td {
    min-width: 100px;
    white-space: nowrap;
}

#dataTable {
    width: 100% !important;
}

.table td, .table th {
    white-space: nowrap;
}

.bulk-delete-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.bulk-delete-btn .badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

<?php include '../Admin/inc/footer.php'; ?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pageLength": 10,
        "responsive": false,
        "scrollX": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        },
        "order": [[2, "asc"]], // Sort by book title by default
        "columnDefs": [
            { 
                "orderable": false, 
                "searchable": false,
                "targets": 0 
            },
            {
                "targets": 1, // ID Range column
                "visible": true,  // Changed from false to true
                "searchable": true // Allow searching ID ranges
            }
        ],
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
            $('#dataTable_filter').addClass('d-flex align-items-center');
            $('#dataTable_filter label').append('<i class="fas fa-search ml-2"></i>');
            $('.dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
        }
    });

    // Add window resize handler
    $(window).on('resize', function () {
        table.columns.adjust();
    });

    // Context menu handling
    let selectedRow = null;
    
    // Hide context menu on document click
    $(document).on('click', function() {
        $('#contextMenu').hide();
    });

    // Prevent context menu on table rows
    $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
        e.preventDefault();
        selectedRow = table.row(this).data();
        
        $('#contextMenu')
            .css({
                top: e.pageY + 'px',
                left: e.pageX + 'px'
            })
            .show();
    });

    // Handle context menu actions
    $('.context-menu-item').on('click', function() {
        const action = $(this).data('action');
        
        if (!selectedRow) return;
        
        if (action === 'edit') {
            window.location.href = 'update_contributors.php?ids=' + selectedRow[1];
        } else if (action === 'delete') {
            let idRanges = selectedRow[1]; // Get ID ranges from the second column
            let bookTitle = selectedRow[2];
            let contributor = selectedRow[3];
            let role = selectedRow[4];

            let totalItems = 0;
            idRanges.split(',').forEach(item => {
                if (item.includes('-')) {
                    const [start, end] = item.split('-').map(Number);
                    totalItems += end - start + 1;
                } else {
                    totalItems++;
                }
            });

            Swal.fire({
                title: 'Are you sure?',
                html: `You are about to delete ${totalItems} contribution(s) from:<br><br>` +
                      `${contributor} (${role})<br>Book: ${bookTitle}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('contributors_list.php', {
                        action: 'delete',
                        ids: idRanges.split(',').map(range => {
                            if (range.includes('-')) {
                                const [start, end] = range.split('-').map(Number);
                                return Array.from({length: end - start + 1}, (_, i) => start + i);
                            }
                            return parseInt(range.trim());
                        }).flat()
                    }, function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error'
                            });
                        }
                    }, 'json');
                }
            });
        }
        
        $('#contextMenu').hide();
    });

    // Modified checkbox handling
    // Header cell click handler
    $(document).on('click', 'thead th:first-child', function(e) {
        // If the click was directly on the checkbox, don't execute this handler
        if (e.target.type === 'checkbox') return;
        
        // Find and click the checkbox
        var checkbox = $('#selectAll');
        checkbox.prop('checked', !checkbox.prop('checked'));
        $('.row-checkbox').prop('checked', checkbox.prop('checked'));
    });

    // Keep the original checkbox change handlers
    $('#selectAll').change(function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#dataTable tbody').on('change', '.row-checkbox', function() {
        if (!$(this).prop('checked')) {
            $('#selectAll').prop('checked', false);
        } else {
            var allChecked = true;
            $('.row-checkbox').each(function() {
                if (!$(this).prop('checked')) allChecked = false;
            });
            $('#selectAll').prop('checked', allChecked);
        }
    });

    // Add cell click handler for the checkbox column
    $('#dataTable tbody').on('click', 'td:first-child', function(e) {
        // If the click was directly on the checkbox, don't execute this handler
        if (e.target.type === 'checkbox') return;
        
        // Find the checkbox within this cell and toggle it
        var checkbox = $(this).find('.row-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Add row click handler to check the row checkbox
    $('#dataTable tbody').on('click', 'tr', function(e) {
        // If the click was directly on the checkbox, don't execute this handler
        if (e.target.type === 'checkbox') return;
        
        // Find the checkbox within this row and toggle it
        var checkbox = $(this).find('.row-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    function updateDeleteButton() {
        const count = $('.row-checkbox:checked').length;
        const deleteBtn = $('.bulk-delete-btn');
        deleteBtn.find('.badge').text(count);
        deleteBtn.prop('disabled', count === 0);
    }

    // Handle checkbox changes
    $('#dataTable').on('change', '.row-checkbox', function() {
        updateDeleteButton();
    });

    // Handle bulk delete
    $('.bulk-delete-btn').on('click', function(e) {
        e.preventDefault();
        
        const selectedRanges = [];
        let selectedBooks = [];
        
        $('.row-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const idRange = row.find('td:eq(1)').text(); // Get ID range from hidden column
            const bookTitle = row.find('td:eq(2)').text();
            selectedRanges.push(idRange);
            selectedBooks.push(bookTitle);
        });

        if (selectedRanges.length === 0) {
            Swal.fire({
                title: 'No Selection',
                text: 'Please select at least one contributor group to delete.',
                icon: 'warning'
            });
            return;
        }

        // Calculate total items to be deleted
        let totalItems = 0;
        selectedRanges.forEach(range => {
            range.split(',').forEach(item => {
                if (item.includes('-')) {
                    const [start, end] = item.split('-').map(Number);
                    totalItems += end - start + 1;
                } else {
                    totalItems++;
                }
            });
        });

        Swal.fire({
            title: 'Are you sure?',
            html: `You are about to delete ${totalItems} contributor(s) from:<br><br>` +
                  selectedBooks.map(book => `- ${book}`).join('<br>'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#selected_ids_container').empty();
                selectedRanges.forEach(range => {
                    range.split(',').forEach(item => {
                        if (item.includes('-')) {
                            const [start, end] = item.split('-').map(Number);
                            for (let id = start; id <= end; id++) {
                                $('#selected_ids_container').append(
                                    `<input type="hidden" name="ids[]" value="${id}">`
                                );
                            }
                        } else {
                            $('#selected_ids_container').append(
                                `<input type="hidden" name="ids[]" value="${item.trim()}">`
                            );
                        }
                    });
                });

                // Submit form with AJAX
                $.ajax({
                    url: 'contributors_list.php',
                    method: 'POST',
                    data: $('#bulkActionForm').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error'
                            });
                        }
                    }
                });
            }
        });
    });
});
</script>
