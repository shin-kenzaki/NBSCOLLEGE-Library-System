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
                <h6 class="m-0 font-weight-bold text-primary">Publications List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Book Title</th>
                                <th>Publisher</th>
                                <th>Place</th>
                                <th>Year</th>
                                <th>Total Books</th>
                            </tr>
                        </thead>
                        <tbody id="publicationsTableBody">
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
            "url": "fetch_publications.php",
            "type": "GET"
        },
        "columns": [
            { 
                "data": null,
                "defaultContent": "",
                "render": function (data, type, row) {
                    return '<input type="checkbox" class="row-checkbox" value="' + row.id + '">';
                },
                "orderable": false
            },
            { "data": "id" },
            { "data": "book_title" },
            { "data": "publisher" },
            { "data": "place" },
            { "data": "publish_date" },
            { 
                "data": "id",
                "render": function(data, type, row) {
                    let total = 0;
                    const ranges = data.split(',').map(r => r.trim());
                    
                    ranges.forEach(range => {
                        if(range.includes('-')) {
                            const [start, end] = range.split('-').map(Number);
                            total += (end - start + 1);
                        } else {
                            total += 1;
                        }
                    });
                    return total;
                }
            }
        ],
        "order": [[2, "asc"], [5, "asc"]] // Sort by book title then year
    });

    // Handle select all checkbox
    $('#selectAll').change(function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
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
});
</script>
