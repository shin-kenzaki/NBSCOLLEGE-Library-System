<?php
session_start();
include '../admin/inc/header.php';
include '../db.php';

$id_ranges = isset($_GET['ids']) ? $_GET['ids'] : '';
$ids = [];

// Convert range string to array of IDs
$ranges = explode(',', $id_ranges);
foreach ($ranges as $range) {
    if (strpos($range, '-') !== false) {
        list($start, $end) = explode('-', trim($range));
        for ($i = (int)$start; $i <= (int)$end; $i++) {
            $ids[] = $i;
        }
    } else {
        $ids[] = (int)trim($range);
    }
}

// Fetch contributor details
$query = "SELECT 
            c.id,
            c.book_id,
            b.title as book_title,
            c.writer_id,
            CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) as writer_name,
            w.firstname,
            w.middle_init,
            w.lastname,
            c.role
          FROM contributors c
          JOIN books b ON c.book_id = b.id
          JOIN writers w ON c.writer_id = w.id
          WHERE c.id IN (" . implode(',', array_map('intval', $ids)) . ")
          ORDER BY b.title, c.id";

$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Update Contributors</h6>
            <div class="d-flex align-items-center">
                <span class="text-muted mr-3">Total Books: <?= count($ids) ?></span>
                <button type="button" class="btn btn-primary btn-sm" id="updateAll">Update All</button>
            </div>
        </div>
        <div class="card-body">
            <form id="updateForm">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Book ID</th>
                                <th>Book Title</th>
                                <th>Writer ID</th>
                                <th>Writer</th>
                                <th>Firstname</th>
                                <th>Middle Initial</th>
                                <th>Lastname</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr data-id="<?= $row['id'] ?>">
                                <td><?= $row['id'] ?></td>
                                <td><?= $row['book_id'] ?></td>
                                <td><?= $row['book_title'] ?></td>
                                <td><?= $row['writer_id'] ?></td>
                                <td><?= $row['writer_name'] ?></td>
                                <td>
                                    <input type="text" class="form-control auto-save" name="firstname_<?= $row['id'] ?>" 
                                           value="<?= $row['firstname'] ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control auto-save" name="middle_init_<?= $row['id'] ?>"
                                           value="<?= $row['middle_init'] ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control auto-save" name="lastname_<?= $row['id'] ?>"
                                           value="<?= $row['lastname'] ?>">
                                </td>
                                <td>
                                    <select class="form-control auto-save" name="role_<?= $row['id'] ?>">
                                        <option value="Author" <?= $row['role'] === 'Author' ? 'selected' : '' ?>>Author</option>
                                        <option value="Co-Author" <?= $row['role'] === 'Co-Author' ? 'selected' : '' ?>>Co-Author</option>
                                        <option value="Editor" <?= $row['role'] === 'Editor' ? 'selected' : '' ?>>Editor</option>
                                    </select>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-save and cascade updates
    $('.auto-save').change(function() {
        const $currentRow = $(this).closest('tr');
        const currentIndex = $currentRow.index();
        const id = $currentRow.data('id');
        const inputName = $(this).attr('name');
        const field = inputName.includes('middle_init') ? 'middle_init' : inputName.split('_')[0];
        const value = $(this).val();
        
        // Get all rows
        const $allRows = $('#updateForm tbody tr');
        const updates = [];

        // If it's the first row, update all rows
        if (currentIndex === 0) {
            $allRows.each(function() {
                const rowId = $(this).data('id');
                // Handle both input and select elements
                if (field === 'role') {
                    $(`select[name="${field}_${rowId}"]`).val(value);
                } else {
                    $(`input[name="${field}_${rowId}"]`).val(value);
                }
                updates.push({
                    id: rowId,
                    [field]: value
                });
            });
        } else {
            // Update current and all following rows except rows 0 to (currentIndex-1)
            $allRows.slice(currentIndex).each(function() {
                const rowId = $(this).data('id');
                // Handle both input and select elements
                if (field === 'role') {
                    $(`select[name="${field}_${rowId}"]`).val(value);
                } else {
                    $(`input[name="${field}_${rowId}"]`).val(value);
                }
                updates.push({
                    id: rowId,
                    [field]: value
                });
            });
        }

        // Send bulk update to server
        $.post('update_contributor.php', {
            updates: updates
        }, function(response) {
            if (!response.success) {
                alert(response.message || 'Update failed');
            }
        }, 'json');
    });

    // Add debug logging
    $('.auto-save').on('change', function() {
        console.log('Field:', $(this).attr('name').split('_')[0]);
        console.log('Value:', $(this).val());
    });

    // Replace the update all button handler with this new implementation
    $('#updateAll').click(function() {
        if (!confirm('Are you sure you want to update all records?')) return;

        // Collect all unique writer combinations
        const combinations = new Map();
        let firstWriterId = null;
        let firstCombination = true;

        $('#updateForm tbody tr').each(function() {
            const $row = $(this);
            const id = $row.data('id');
            const writerId = $row.find('td:eq(3)').text();
            const firstname = $(`input[name="firstname_${id}"]`).val().trim();
            const middleInit = $(`input[name="middle_init_${id}"]`).val().trim();
            const lastname = $(`input[name="lastname_${id}"]`).val().trim();
            const role = $(`select[name="role_${id}"]`).val().trim(); // Changed to select
            const key = `${firstname}|${middleInit}|${lastname}|${role}`;

            if (firstCombination) {
                firstWriterId = writerId;
                firstCombination = false;
            }

            // Store the combination if not seen before
            if (!combinations.has(key)) {
                combinations.set(key, {
                    firstname: firstname,
                    middle_init: middleInit,
                    lastname: lastname,
                    role: role,
                    writerId: combinations.size === 0 ? firstWriterId : null, // Only first combination keeps existing writer_id
                    contributors: []
                });
            }

            // Add this contributor to the combination
            combinations.get(key).contributors.push(id);
        });

        // Send the update request
        $.ajax({
            url: 'update_contributor.php',
            method: 'POST',
            data: {
                bulk_update: true,
                combinations: Array.from(combinations).map(([key, value]) => ({
                    firstname: value.firstname,
                    middle_init: value.middle_init,
                    lastname: value.lastname,
                    role: value.role,
                    writerId: value.writerId,
                    contributors: value.contributors
                }))
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('All records updated successfully');
                    location.reload();
                } else {
                    alert(response.message || 'Update failed');
                }
            },
            error: function() {
                alert('Error occurred during update');
            }
        });
    });
});
</script>

<?php include '../Admin/inc/footer.php'; ?>
