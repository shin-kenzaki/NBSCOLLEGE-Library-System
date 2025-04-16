<?php
session_start();
include '../db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// Get user ID - check both possible session variables
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

// Get user type to determine borrowing limit
$userTypeQuery = "SELECT usertype FROM users WHERE id = ?";
$stmt = $conn->prepare($userTypeQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$userTypeResult = $stmt->get_result();
$userType = 'Student'; // Default to student (3 limit)
$maxItems = 3; // Default limit

if ($userTypeResult->num_rows > 0) {
    $userType = $userTypeResult->fetch_assoc()['usertype'];
    // If user is faculty or staff, set limit to 5
    if (strtolower($userType) == 'faculty' || strtolower($userType) == 'staff') {
        $maxItems = 5;
    }
}

// Handle removing items from cart
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $cartItemId = intval($_GET['id']);
    $deleteQuery = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $cartItemId, $userId);
    $result = $stmt->execute();
    
    // If this is an AJAX request, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $response = [
            'success' => $result,
            'message' => $result ? 'Item removed successfully' : 'Failed to remove item'
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // For non-AJAX requests, redirect to clear the parameters
    header("Location: cart.php");
    exit;
}

// Check for overdue books
$overdueCheckQuery = "SELECT COUNT(*) AS overdue_count 
                     FROM borrowings 
                     WHERE user_id = ? 
                     AND status = 'Borrowed' 
                     AND due_date < CURRENT_DATE()";
$stmt = $conn->prepare($overdueCheckQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$overdueResult = $stmt->get_result();
$overdueCount = $overdueResult->fetch_assoc()['overdue_count'];

// Get active borrowings and reservations count
$activeBorrowingsQuery = "SELECT COUNT(*) as count FROM borrowings 
                         WHERE user_id = ? AND status = 'Active'";
$stmt = $conn->prepare($activeBorrowingsQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$activeBorrowingsResult = $stmt->get_result();
$activeBorrowings = $activeBorrowingsResult->fetch_assoc()['count'];

$activeReservationsQuery = "SELECT COUNT(*) as count FROM reservations 
                          WHERE user_id = ? AND status IN ('Pending', 'Reserved', 'Ready')";
$stmt = $conn->prepare($activeReservationsQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$activeReservationsResult = $stmt->get_result();
$activeReservations = $activeReservationsResult->fetch_assoc()['count'];

$currentTotal = $activeBorrowings + $activeReservations;
$remainingSlots = $maxItems - $currentTotal;

// Get cart items
$cartQuery = "SELECT c.id, b.id AS book_id, b.title, b.ISBN, b.accession, b.series, b.volume, b.part, b.edition, b.call_number 
             FROM cart c 
             JOIN books b ON c.book_id = b.id 
             WHERE c.user_id = ? AND c.status = 1
             ORDER BY c.date DESC";
$stmt = $conn->prepare($cartQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$cartItems = $stmt->get_result();

include 'inc/header.php';
?>

<!-- Begin Page Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">My Cart</h1>
        </div>

        <!-- Cart Status Alert -->
        <?php if ($overdueCount > 0): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> You have <?php echo $overdueCount; ?> overdue book(s). Please return them before checking out or making new reservations.
            </div>
        <?php endif; ?>

        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-2"></i> 
            As a <?php echo $userType; ?>, you can borrow or reserve up to <?php echo $maxItems; ?> items at once.
            You currently have <?php echo $currentTotal; ?> active item(s) (borrowed or reserved).
            <?php if ($remainingSlots > 0): ?>
                You can check out up to <?php echo $remainingSlots; ?> more item(s).
            <?php else: ?>
                You cannot check out any more items until you return some.
            <?php endif; ?>
        </div>

        <!-- Cart Content -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Your Cart Items</h6>
                <?php if ($cartItems->num_rows > 0 && $remainingSlots > 0): ?>
                    <button id="checkoutBtn" class="btn btn-success btn-sm">
                        <i class="fas fa-shopping-cart me-2"></i> Checkout Selected (<span id="selectedCount">0</span>)
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($cartItems->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-no-lines" id="cartTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center check-column">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </th>
                                    <th class="details-column">Title</th>
                                    <th width="12%">Accession</th>
                                    <th width="12%">Call Number</th>
                                    <th width="25%">Details</th>
                                    <th width="10%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $cartItems->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center checkbox-cell check-column">
                                            <input class="form-check-input item-select" type="checkbox" value="<?php echo $item['id']; ?>" 
                                                <?php if ($remainingSlots <= 0) echo 'disabled'; ?>>
                                        </td>
                                        <td class="book-details">
                                            <div class="book-details-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['accession']); ?></td>
                                        <td><?php echo htmlspecialchars($item['call_number'] ?: 'N/A'); ?></td>
                                        <td class="book-details-info">
                                            <?php
                                            $details = [];
                                            if (!empty($item['edition'])) $details[] = "Edition: " . htmlspecialchars($item['edition']);
                                            if (!empty($item['series'])) $details[] = "Series: " . htmlspecialchars($item['series']);
                                            if (!empty($item['volume'])) $details[] = "Volume: " . htmlspecialchars($item['volume']);
                                            if (!empty($item['part'])) $details[] = "Part: " . htmlspecialchars($item['part']);
                                            if (!empty($item['ISBN'])) $details[] = "ISBN: " . htmlspecialchars($item['ISBN']);
                                            
                                            echo !empty($details) ? implode("<br>", $details) : 'N/A';
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="view_book.php?book_id=<?php echo $item['book_id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="cart.php?action=remove&id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm remove-cart-item" data-id="<?php echo $item['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-table-message">
                        <div class="alert alert-info">
                            <i class="fas fa-shopping-cart me-2"></i> Your cart is empty.
                            <div class="mt-3">
                                <a href="searchbook.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Browse Books
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
<!-- /.container-fluid -->

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Fix checkbox alignment in tables - improved centering */
    .table th.check-column,
    .table td.check-column {
        text-align: center;
        vertical-align: middle;
        width: 40px !important;
        min-width: 40px !important;
        padding: 0.75rem;
        cursor: pointer;
    }
    
    /* Remove absolute positioning that was causing issues */
    .table th input[type="checkbox"],
    .table td input[type="checkbox"] {
        cursor: pointer;
        position: relative;
        margin: 0 auto;
        display: block;
    }
    
    /* Enhanced checkbox cell styling */
    .checkbox-cell {
        text-align: center;
        vertical-align: middle;
        position: relative;
        width: 40px !important;
        min-width: 40px !important;
        padding: 0.75rem !important;
    }
    
    /* Improve checkbox visibility */
    .form-check-input {
        width: 20px;
        height: 20px;
        cursor: pointer;
        margin: 0 auto;
        display: block;
    }
    
    /* Highlight checkbox row on hover */
    #cartTable tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    /* Highlight selected rows */
    #cartTable tbody tr.selected-row {
        background-color: rgba(0, 123, 255, 0.1);
    }
    
    /* Book details styling */
    .book-details-title {
        font-weight: bold;
        color: #4e73df;
        margin-bottom: 5px;
    }
    
    .book-details-info {
        color: #666;
        font-size: 0.9em;
    }
    
    .empty-table-message {
        text-align: center;
        padding: 20px;
        font-size: 1.1em;
        color: #666;
    }
    
    /* Table styling without vertical lines */
    .table-no-lines {
        border-collapse: collapse;
    }
    .table-no-lines th,
    .table-no-lines td {
        border: none;
        border-bottom: 1px solid #e3e6f0;
    }
    .table-no-lines thead th {
        border-bottom: 2px solid #e3e6f0;
        background-color: #f8f9fc;
    }
    
    /* Fixed width for columns to improve layout consistency */
    .check-column {
        width: 5% !important;
        max-width: 40px !important;
    }
    
    .details-column {
        width: 35% !important;
    }
    
    /* Highlight checkbox row on hover */
    #cartTable tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle select all checkbox
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-select:not([disabled])');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
                // Highlight selected rows
                const row = checkbox.closest('tr');
                if(row) {
                    if(selectAll.checked) {
                        row.classList.add('selected-row');
                    } else {
                        row.classList.remove('selected-row');
                    }
                }
            });
            updateCheckoutButtonState();
        });
    }
    
    // Handle individual checkboxes
    document.querySelectorAll('.item-select').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateCheckoutButtonState();
            
            // Highlight the entire row when selected
            const row = this.closest('tr');
            if(row) {
                if(this.checked) {
                    row.classList.add('selected-row');
                } else {
                    row.classList.remove('selected-row');
                }
            }
            
            // Update selectAll checkbox state
            updateSelectAllState();
        });
    });
    
    // Function to update "Select All" checkbox state
    function updateSelectAllState() {
        if (!selectAll) return;
        
        const checkboxes = document.querySelectorAll('.item-select:not([disabled])');
        const checkedCheckboxes = document.querySelectorAll('.item-select:checked');
        
        if (checkboxes.length === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        } else if (checkedCheckboxes.length === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        } else if (checkedCheckboxes.length === checkboxes.length) {
            selectAll.checked = true;
            selectAll.indeterminate = false;
        } else {
            selectAll.checked = false;
            selectAll.indeterminate = true;
        }
    }
    
    // Update checkout button state and selected count
    function updateCheckoutButtonState() {
        const selectedCount = document.querySelectorAll('.item-select:checked').length;
        const selectedCountElement = document.getElementById('selectedCount');
        if (selectedCountElement) {
            selectedCountElement.textContent = selectedCount;
        }
    }
    
    // Enhanced checkbox cell click handler - improved to be more reliable
    document.querySelectorAll('td.check-column, th.check-column').forEach(cell => {
        cell.addEventListener('click', function(e) {
            // Only handle if the click wasn't directly on the checkbox
            if (e.target.type !== 'checkbox') {
                const checkbox = cell.querySelector('input[type="checkbox"]');
                if (checkbox && !checkbox.disabled) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            }
            e.stopPropagation();
        });
    });
    
    // Enhanced row click handler - make entire row clickable for better UX
    document.querySelectorAll('#cartTable tbody tr').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            // Ignore clicks on buttons, links or checkboxes
            if (e.target.tagName === 'A' || 
                e.target.tagName === 'BUTTON' || 
                e.target.tagName === 'I' || 
                e.target.type === 'checkbox' ||
                e.target.closest('a') || 
                e.target.closest('button')) {
                return;
            }
            
            const checkbox = this.querySelector('.item-select');
            if (checkbox && !checkbox.disabled) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    });
    
    // Initialize the checkout button state and select all state
    updateCheckoutButtonState();
    updateSelectAllState();
    
    // Handle checkout button
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            const selectedItems = document.querySelectorAll('.item-select:checked');
            
            if (selectedItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Items Selected',
                    text: 'Please select at least one item to checkout.'
                });
                return;
            }
            
            // Check if selection exceeds remaining slots
            if (selectedItems.length > <?php echo $remainingSlots; ?>) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Too Many Items',
                    text: 'You can only check out up to <?php echo $remainingSlots; ?> more items.'
                });
                return;
            }
            
            // Collect selected cart IDs
            const selectedCartIds = Array.from(selectedItems).map(item => item.value);
            
            // Confirm checkout
            Swal.fire({
                icon: 'question',
                title: 'Confirm Checkout',
                text: 'Do you want to checkout the selected items?',
                showCancelButton: true,
                confirmButtonText: 'Yes, checkout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we process your checkout',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Send request
                    $.ajax({
                        type: 'POST',
                        url: 'checkout.php',
                        data: { 
                            selected_cart_ids: selectedCartIds
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Checkout Successful',
                                    html: response.message
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Checkout Failed',
                                    html: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An unexpected error occurred during checkout.'
                            });
                        }
                    });
                }
            });
        });
    }
    
    // Handle remove cart item button
    document.querySelectorAll('.remove-cart-item').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const removeUrl = this.href;
            const cartRow = this.closest('tr');
            const cartItemId = this.getAttribute('data-id');
            
            Swal.fire({
                title: 'Remove Item',
                text: 'Are you sure you want to remove this item from your cart?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, remove it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show mini loading indicator
                    cartRow.style.opacity = '0.5';
                    
                    // Send AJAX request to remove the item
                    fetch(removeUrl, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Animate row removal
                            cartRow.style.transition = 'all 0.3s ease';
                            cartRow.style.height = '0';
                            cartRow.style.opacity = '0';
                            
                            // Remove the row after animation
                            setTimeout(() => {
                                cartRow.remove();
                                
                                // Update selectAll checkbox if no items left
                                const remainingCheckboxes = document.querySelectorAll('.item-select').length;
                                if (remainingCheckboxes === 0) {
                                    if (document.getElementById('selectAll')) {
                                        document.getElementById('selectAll').checked = false;
                                    }
                                }
                                
                                // Check if table is now empty
                                if (document.querySelectorAll('#cartTable tbody tr').length === 0) {
                                    document.querySelector('.card-body').innerHTML = `
                                        <div class="empty-table-message">
                                            <div class="alert alert-info">
                                                <i class="fas fa-shopping-cart me-2"></i> Your cart is empty.
                                                <div class="mt-3">
                                                    <a href="searchbook.php" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-search"></i> Browse Books
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                    
                                    // Hide the checkout button if it exists
                                    const checkoutBtn = document.getElementById('checkoutBtn');
                                    if (checkoutBtn) {
                                        checkoutBtn.style.display = 'none';
                                    }
                                }
                                
                                // Update the checkout button counter
                                updateCheckoutButtonState();
                            }, 300);
                            
                            // Show success notification
                            Swal.fire({
                                icon: 'success',
                                title: 'Removed!',
                                text: 'The item has been removed from your cart.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            // Restore row appearance and show error
                            cartRow.style.opacity = '1';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to remove the item. Please try again.'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        cartRow.style.opacity = '1';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An unexpected error occurred. Please try again.'
                        });
                    });
                }
            });
        });
    });
});
</script>

<?php include 'inc/footer.php'; ?>
