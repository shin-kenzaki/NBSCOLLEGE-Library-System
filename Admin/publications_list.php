<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';

// Query to fetch publications data
$query = "SELECT 
            GROUP_CONCAT(p.id ORDER BY p.id) as id_ranges,
            b.title as book_title,
            pb.publisher,
            pb.place,
            p.publish_date
          FROM publications p 
          JOIN books b ON p.book_id = b.id 
          JOIN publishers pb ON p.publisher_id = pb.id
          GROUP BY b.title, pb.id, p.publish_date
          ORDER BY b.title, p.publish_date";

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
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Publications List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="cursor: pointer;" id="checkboxHeader"><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Book Title</th>
                                <th>Publisher</th>
                                <th>Place</th>
                                <th>Year</th>
                                <th>Total Books</th>
                            </tr>
                        </thead>
                        <tbody id="publicationsTableBody">
                            <?php foreach ($publications_data as $row): ?>
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($row['id_ranges']); ?>"></td>
                                <td><?php echo htmlspecialchars($row['id_ranges']); ?></td>
                                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['publisher']); ?></td>
                                <td><?php echo htmlspecialchars($row['place']); ?></td>
                                <td><?php echo htmlspecialchars(date('Y', strtotime($row['publish_date']))); ?></td>
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
        "responsive": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        },
        "order": [[2, "asc"], [5, "asc"]], // Sort by book title then year
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
            if (confirm('Are you sure you want to delete all publications with these IDs: ' + selectedRow.id + '?')) {
                $.post('delete_publications.php', {
                    ids: selectedRow.id  // This now contains the ID ranges
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
