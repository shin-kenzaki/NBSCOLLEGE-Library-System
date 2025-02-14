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
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Contributors List</h6>
                <div class="bulk-actions">
                    <button class="btn btn-primary" id="editSelected">Edit Selected</button>
                    <button class="btn btn-danger" id="deleteSelected">Delete Selected</button>
                </div>
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
                }
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
                        if(range.includes('-')) {
                            const [start, end] = range.split('-').map(Number);
                            total += (end - start + 1);
                        } else {
                            total += 1;
                        }
                    });
                    
                    // Example: "1-30, 81-90" would return 40 (30 + 10 books)
                    return total;
                }
            }
        ]
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

    // Handle bulk edit button
    $('#editSelected').click(function() {
        var selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            var ids = $(this).val().split(',');
            selectedIds = selectedIds.concat(ids);
        });
        
        if (selectedIds.length > 0) {
            window.location.href = 'edit_contributor.php?ids=' + selectedIds.join(',');
        } else {
            alert('Please select at least one contributor to edit.');
        }
    });

    // Handle bulk delete button
    $('#deleteSelected').click(function() {
        var selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            var ids = $(this).val().split(',');
            selectedIds = selectedIds.concat(ids);
        });
        
        if (selectedIds.length > 0) {
            if (confirm('Are you sure you want to delete the selected contributors?')) {
                $.post('delete_contributors.php', {
                    ids: selectedIds
                }, function(response) {
                    if (response.success) {
                        table.ajax.reload();
                    }
                    alert(response.message);
                });
            }
        } else {
            alert('Please select at least one contributor to delete.');
        }
    });
});
</script>
