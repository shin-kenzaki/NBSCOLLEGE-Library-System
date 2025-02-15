<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';

// Get search parameters
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Contributors List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
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
                            <!-- Content will be loaded via AJAX -->
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
        "ajax": {
            "url": "fetch_contributors.php",
            "type": "GET"
        },
        "columns": [
            { 
                "data": null,
                "defaultContent": "",
                "render": function (data, type, row) {
                    return '<input type="checkbox" class="row-checkbox" value="' + row.id_ranges + '">';
                },
                "orderable": false
            },
            { "data": "id_ranges" },
            { "data": "book_title" },
            { "data": "writer_name" },
            { "data": "role" },
            { 
                "data": "id_ranges",
                "render": function(data, type, row) {
                    let total = 0;
                    const ranges = data.split(',').map(r => r.trim());
                    ranges.forEach(range => {
                        if (range.includes('-')) {
                            const [start, end] = range.split('-').map(Number);
                            total += (end - start + 1);
                        } else {
                            total += 1;
                        }
                    });
                    return total;
                }
            }
        ]
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
