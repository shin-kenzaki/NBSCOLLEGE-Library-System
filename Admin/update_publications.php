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

// Fetch publication details
$query = "SELECT 
            p.id,
            p.book_id,
            b.title as book_title,
            p.publisher_id,
            pb.publisher,
            pb.place,
            p.publish_date
          FROM publications p 
          JOIN books b ON p.book_id = b.id 
          JOIN publishers pb ON p.publisher_id = pb.id
          WHERE p.id IN (" . implode(',', array_map('intval', $ids)) . ")
          ORDER BY b.title, p.id";

$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Update Publications</h6>
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
                                <th>Publisher ID</th>
                                <th>Publisher</th>
                                <th>Place</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr data-id="<?= $row['id'] ?>">
                                <td><?= $row['id'] ?></td>
                                <td><?= $row['book_id'] ?></td>
                                <td><?= $row['book_title'] ?></td>
                                <td><?= $row['publisher_id'] ?></td>
                                <td>
                                    <input type="text" class="form-control auto-save" name="publisher_<?= $row['id'] ?>" 
                                           value="<?= $row['publisher'] ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control auto-save" name="place_<?= $row['id'] ?>" 
                                           value="<?= $row['place'] ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control auto-save" name="year_<?= $row['id'] ?>"
                                           value="<?= $row['publish_date'] ?>" min="1900" max="<?= date('Y') ?>">
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
        const field = $(this).attr('name').split('_')[0]; // 'place' or 'year'
        const value = $(this).val();
        
        // Get all rows
        const $allRows = $('#updateForm tbody tr');
        const updates = [];

        // If it's the first row, update all rows
        if (currentIndex === 0) {
            $allRows.each(function() {
                const rowId = $(this).data('id');
                $(`input[name="${field}_${rowId}"]`).val(value);
                updates.push({
                    id: rowId,
                    [field]: value
                });
            });
        } else {
            // Update current and all following rows except rows 0 to (currentIndex-1)
            $allRows.slice(currentIndex).each(function() {
                const rowId = $(this).data('id');
                $(`input[name="${field}_${rowId}"]`).val(value);
                updates.push({
                    id: rowId,
                    [field]: value
                });
            });
        }

        // Send bulk update to server
        $.post('update_publication.php', {
            updates: updates
        }, function(response) {
            if (!response.success) {
                alert(response.message || 'Update failed');
            }
        }, 'json'); // Add 'json' as dataType
    });

    // Replace the update all button handler with this new implementation
    $('#updateAll').click(function() {
        if (!confirm('Are you sure you want to update all records?')) return;

        // Collect all unique publisher-place-year combinations
        const combinations = new Map();
        const updates = [];
        let firstPublisherId = null;

        $('#updateForm tbody tr').each(function() {
            const $row = $(this);
            const id = $row.data('id');
            const publisherId = $row.find('td:eq(3)').text();
            const publisher = $(`input[name="publisher_${id}"]`).val().trim();
            const place = $(`input[name="place_${id}"]`).val().trim();
            const year = $(`input[name="year_${id}"]`).val().trim();
            const key = `${publisher}|${place}|${year}`;

            if (!firstPublisherId) {
                firstPublisherId = publisherId;
            }

            // Store the combination if not seen before
            if (!combinations.has(key)) {
                combinations.set(key, {
                    publisher: publisher,
                    place: place,
                    year: year,
                    publisherId: combinations.size === 0 ? firstPublisherId : null, // Use existing publisher ID only for first combination
                    publications: []
                });
            }

            // Add this publication to the combination
            combinations.get(key).publications.push(id);
        });

        // Send the update request
        $.ajax({
            url: 'update_publication.php',
            method: 'POST',
            data: {
                bulk_update: true,
                combinations: Array.from(combinations).map(([key, value]) => ({
                    publisher: value.publisher,
                    place: value.place,
                    year: value.year,
                    publisherId: value.publisherId,
                    publications: value.publications
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
