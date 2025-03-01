<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';

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
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Contributors List</h6>
            </div>
            <div class="card-body px-0">
                <div class="table-responsive px-3">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID Range</th>
                                <th>Book Title</th>
                                <th>Contributor</th>
                                <th>Role</th>
                                <th>Total Books</th>
                            </tr>
                        </thead>
                        <tbody id="contributorsTableBody">
                            <?php foreach ($contributors_data as $row): ?>
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($row['id_ranges']); ?>"></td>
                                <td><?php echo htmlspecialchars($row['id_ranges']); ?></td>
                                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['writer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['role']); ?></td>
                                <td><?php 
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
</style>

<?php include '../Admin/inc/footer.php'; ?>

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
            window.location.href = 'update_contributors.php?ids=' + selectedRow.id_ranges;
        } else if (action === 'delete') {
            if (confirm('Are you sure you want to delete all contributors with these IDs: ' + selectedRow.id_ranges + '?')) {
                $.post('delete_contributors.php', {
                    ids: selectedRow.id_ranges  // Pass the full ID ranges string
                }, function(response) {
                    if (response.success) {
                        table.ajax.reload();
                    }
                    alert(response.message);
                }, 'json');
            }
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
});
</script>
