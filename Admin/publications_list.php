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
            $stmt = $conn->prepare("DELETE FROM publications WHERE id = ?");
            
            foreach ($_POST['ids'] as $id) {
                $id = (int)$id;
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $deleteCount++;
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "$deleteCount publication(s) deleted successfully."]);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => "Error deleting publications: " . $e->getMessage()]);
            exit();
        }
    }
}

// Include other files after header operations
include '../admin/inc/header.php';
include '../db.php';

// Initialize selected publications array in session if not exists
if (!isset($_SESSION['selectedPublicationIds'])) {
    $_SESSION['selectedPublicationIds'] = [];
}

// Query to fetch publications data
$query = "SELECT 
            GROUP_CONCAT(p.id ORDER BY p.id) AS id_ranges,
            pb.publisher,
            pb.place,
            b.title AS book_title,
            GROUP_CONCAT(DISTINCT YEAR(p.publish_date) ORDER BY YEAR(p.publish_date)) AS publish_years,
            COUNT(p.id) AS total_books
          FROM publications p 
          JOIN books b ON p.book_id = b.id 
          JOIN publishers pb ON p.publisher_id = pb.id
          GROUP BY pb.publisher, pb.place, b.title  -- Group by publisher, place, and title
          ORDER BY pb.publisher, b.title";

$result = $conn->query($query);
$publications_data = array();

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
    $publications_data[] = $row;
}
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Publications List</h6>
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
                                <th style='text-align: center;'>ID</th>
                                <th style='text-align: center;'>Publisher</th>
                                <th style='text-align: center;'>Place</th>
                                <th style='text-align: center;'>Book Title</th>
                                <th style='text-align: center;'>Years</th>
                                <th style='text-align: center;'>Total Books</th>
                            </tr>
                        </thead>
                        <tbody id="publicationsTableBody">
                            <?php foreach ($publications_data as $row): ?>
                            <tr>
                                <td style='text-align: center;'><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($row['id_ranges']); ?>"></td>
                                <td style='text-align: center;'><?php echo htmlspecialchars($row['id_ranges']); ?></td>
                                <td><?php echo htmlspecialchars($row['publisher']); ?></td>
                                <td style='text-align: center;'><?php echo htmlspecialchars($row['place']); ?></td>
                                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                                <td style='text-align: center;'><?php echo htmlspecialchars($row['publish_years']); ?></td>
                                <td style='text-align: center;'><?php echo htmlspecialchars($row['total_books']); ?></td>
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
<form id="bulkActionForm" method="POST" action="publications_list.php">
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
        "order": [[2, "asc"], [4, "asc"]], // Sort by publisher then book title
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

    // Handle select all checkbox and header click
    $('#selectAll, #checkboxHeader').on('click', function(e) {
        if ($(this).is('th')) {
            // If clicking the header cell, toggle the checkbox
            const checkbox = $('#selectAll');
            checkbox.prop('checked', !checkbox.prop('checked'));
        }
        // Apply the checkbox state to all row checkboxes
        $('.row-checkbox').prop('checked', $('#selectAll').prop('checked'));
        // Prevent event bubbling when clicking the checkbox itself
        if ($(this).is('input')) {
            e.stopPropagation();
        }
    });

    // Handle individual checkbox changes
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
            window.location.href = 'update_publications.php?ids=' + selectedRow.id;
        } else if (action === 'delete') {
            let idRanges = selectedRow[1]; // Get ID ranges from the second column
            let publisher = selectedRow[2];
            let year = selectedRow[4];

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
                html: `You are about to delete ${totalItems} publication(s) from:<br><br>` +
                      `${publisher} (${year})`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('publications_list.php', {
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

    // Add window resize handler
    $(window).on('resize', function () {
        table.columns.adjust();
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
        let selectedPublications = [];
        
        $('.row-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const idRange = row.find('td:eq(1)').text(); // Get ID range from hidden column
            const publisher = row.find('td:eq(2)').text();
            const year = row.find('td:eq(4)').text();
            selectedRanges.push(idRange);
            selectedPublications.push(`${publisher} (${year})`);
        });

        if (selectedRanges.length === 0) {
            Swal.fire({
                title: 'No Selection',
                text: 'Please select at least one publication group to delete.',
                icon: 'warning'
            });
            return;
        }

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
            html: `You are about to delete ${totalItems} publication(s) from:<br><br>` +
                  selectedPublications.map(pub => `- ${pub}`).join('<br>'),
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
                    url: 'publications_list.php',
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
