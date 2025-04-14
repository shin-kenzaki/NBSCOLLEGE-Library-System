<?php
session_start();
include('inc/header.php');

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Check if the user has the correct role
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian') {
    header('Location: dashboard.php');
    exit();
}

include('../db.php');

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$userFilter = isset($_GET['user']) ? $_GET['user'] : '';
$bookFilter = isset($_GET['book']) ? $_GET['book'] : '';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "WHERE 1=1";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= " AND f.status = '$statusFilter'";
    $filterParams[] = "status=$statusFilter";
}

if ($dateStart) {
    $whereClause .= " AND DATE(f.date) >= '$dateStart'";
    $filterParams[] = "date_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= " AND DATE(f.date) <= '$dateEnd'";
    $filterParams[] = "date_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "user=" . urlencode($userFilter);
}

if ($bookFilter) {
    $whereClause .= " AND bk.title LIKE '%$bookFilter%'";
    $filterParams[] = "book=" . urlencode($bookFilter);
}

if ($typeFilter) {
    $whereClause .= " AND f.type = '$typeFilter'";
    $filterParams[] = "type=$typeFilter";
}

// Fetch fines with related information
$query = "SELECT f.id, f.type, f.amount, f.status, f.date, f.payment_date,
       f.reminder_sent, f.invoice_sale,
       b.issue_date, b.due_date, b.return_date,
       bk.title AS book_title, bk.accession,
       CONCAT(u.firstname, ' ', u.lastname) AS borrower_name,
       u.school_id, u.department, u.usertype -- Include department and usertype
FROM fines f
JOIN borrowings b ON f.borrowing_id = b.id
JOIN books bk ON b.book_id = bk.id
JOIN users u ON b.user_id = u.id
$whereClause
ORDER BY f.date DESC";

// Run the query and store the result
$result = $conn->query($query);

// Count total number of records for the filter summary
$countQuery = "SELECT COUNT(*) as total FROM fines f
              JOIN borrowings b ON f.borrowing_id = b.id
              JOIN books bk ON b.book_id = bk.id
              JOIN users u ON b.user_id = u.id
              $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

// Get distinct fine types for dropdown filter
$typeQuery = "SELECT DISTINCT type FROM fines ORDER BY type";
$typeResult = $conn->query($typeQuery);
$fineTypes = [];
while($row = $typeResult->fetch_assoc()) {
    $fineTypes[] = $row['type'];
}

// Fetch total number of unpaid fines and total value of unpaid fines
$unpaidFinesQuery = "SELECT COUNT(*) as total_unpaid_fines, SUM(amount) as total_unpaid_value FROM fines WHERE status = 'Unpaid'";
$unpaidFinesResult = $conn->query($unpaidFinesQuery);
$unpaidFinesRow = $unpaidFinesResult->fetch_assoc();
$totalUnpaidFines = $unpaidFinesRow['total_unpaid_fines'] ?: 0;
$totalUnpaidValue = $unpaidFinesRow['total_unpaid_value'] ?: 0;

// Fetch total number of paid fines and total value of paid fines
$paidFinesQuery = "SELECT COUNT(*) as total_paid_fines, SUM(amount) as total_paid_value FROM fines WHERE status = 'Paid'";
$paidFinesResult = $conn->query($paidFinesQuery);
$paidFinesRow = $paidFinesResult->fetch_assoc();
$totalPaidFines = $paidFinesRow['total_paid_fines'] ?: 0;
$totalPaidValue = $paidFinesRow['total_paid_value'] ?: 0;
?>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .table td, .table th {
        white-space: nowrap;
    }
    .stats-card {
        transition: all 0.3s;
        border-left: 4px solid;
    }
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .stats-icon {
        font-size: 2rem;
        opacity: 0.6;
    }
    .stats-title {
        font-size: 0.9rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    .stats-number {
        font-size: 1.5rem;
        font-weight: bold;
    }
    .unpaid-card {
        border-left-color: #e74a3b;
    }
    .paid-card {
        border-left-color: #1cc88a;
    }

    .swal-wide {
        width: 80% !important; /* Adjust the width as needed */
        max-width: 1000px !important; /* Set a maximum width */
    }

    .swal-wide .swal2-html-container {
        text-align: left; /* Ensure the content is left-aligned */
        max-height: none !important; /* Remove height restrictions */
        overflow: visible !important; /* Prevent scrolling */
    }
</style>

<!-- Main Content -->
    <div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Fines</h1>

            <!-- Generate Receipt Form -->
            <!-- <form action="fine-receipt-invoice.php" method="post" id="receiptForm" target="_blank" onsubmit="return validateForm()" class="d-flex align-items-center">
                <div class="col-auto p-2">
                    <label for="school_id" class="col-form-label" style="font-size:medium;">Enter ID Number:</label>
                </div>
                <div class="col-auto p-2" style="width:200px;">
                    <input type="text" name="school_id" id="school_id" class="form-control custom" placeholder="ID Number" required>
                </div>
                <div class="col-auto p-2">
                    <button class="btn btn-danger btn-block" type="submit">Generate Fine Receipt</button>
                </div>
            </form> -->
        </div>

        <!-- Fines Filter Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Filter Fines</h6>
                <button class="btn btn-sm btn-primary" id="toggleFilter">
                    <i class="fas fa-filter"></i> Toggle Filter
                </button>
            </div>
            <div class="card-body <?= empty($filterParams) ? 'd-none' : '' ?>" id="filterForm">
                <form method="get" action="" class="mb-0" id="finesFilterForm">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control form-control-sm" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="Paid" <?= ($statusFilter == 'Paid') ? 'selected' : '' ?>>Paid</option>
                                    <option value="Unpaid" <?= ($statusFilter == 'Unpaid') ? 'selected' : '' ?>>Unpaid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="date_start">From Date</label>
                                <input type="date" class="form-control form-control-sm" id="date_start"
                                       name="date_start" value="<?= $dateStart ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="date_end">To Date</label>
                                <input type="date" class="form-control form-control-sm" id="date_end"
                                       name="date_end" value="<?= $dateEnd ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="user">Borrower</label>
                                <input type="text" class="form-control form-control-sm" id="user"
                                       name="user" placeholder="Name or ID" value="<?= htmlspecialchars($userFilter) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="book">Book</label>
                                <input type="text" class="form-control form-control-sm" id="book"
                                       name="book" placeholder="Title" value="<?= htmlspecialchars($bookFilter) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="type">Fine Type</label>
                                <select class="form-control form-control-sm" id="type" name="type">
                                    <option value="">All Types</option>
                                    <?php foreach($fineTypes as $type): ?>
                                    <option value="<?= $type ?>" <?= ($typeFilter == $type) ? 'selected' : '' ?>><?= $type ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" id="applyFilters" class="btn btn-primary btn-sm mr-2">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <button type="button" id="resetFilters" class="btn btn-secondary btn-sm">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Fines List</h6>
                <div>
                    <!-- Results summary -->
                    <span id="filterSummary" class="mr-3 <?= empty($filterParams) ? 'd-none' : '' ?>">
                        <span class="text-primary font-weight-bold">Filter applied:</span>
                        Showing <span id="totalResults"><?= $totalRecords ?></span> result<span id="pluralSuffix"><?= $totalRecords != 1 ? 's' : '' ?></span>
                    </span>
                    <button id="generateReceiptBtn" class="btn btn-primary btn-sm mr-2">Generate Receipt</button>
                    <button id="remindAllBtn" class="btn btn-warning btn-sm mr-2">Remind All</button>
                    <button id="exportPaidFinesBtn" class="btn btn-success btn-sm mr-2">Export Paid Fines</button>
                    <button id="exportUnpaidFinesBtn" class="btn btn-danger btn-sm">Export Unpaid Fines</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="finesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center">
                                <input type="checkbox" id="selectAll" />
                            </th>
                            <th class="text-center">Borrower ID</th>
                            <th class="text-center">Borrower</th>
                            <th class="text-center">Book</th>
                            <th class="text-center">Accession No</th> <!-- New Column -->
                            <th class="text-center">Type</th>
                            <th class="text-center">Amount</th>

                            <th class="text-center">Issue Date</th>
                            <th class="text-center">Date Due</th>
                            <th class="text-center">Fine Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Payment Date</th>
                            <th class="text-center">Invoice/OR</th>
                            <th class="text-center">Reminder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr data-fine-id="<?php echo $row['id']; ?>"
                                    data-amount="<?php echo $row['amount']; ?>"
                                    data-borrower="<?php echo htmlspecialchars($row['borrower_name']); ?>"
                                    data-course="<?php echo htmlspecialchars($row['department']); ?>"
                                    data-user-type="<?php echo htmlspecialchars($row['usertype']); ?>"
                                    data-status="<?php echo $row['status']; ?>">
                                    <td class="text-center">
                                        <input type="checkbox" class="fineCheckbox" value="<?php echo $row['id']; ?>" />
                                    </td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['school_id']); ?></td>
                                    <td class="text-left"><?php echo htmlspecialchars($row['borrower_name']); ?></td>
                                    <td class="text-left"><?php echo htmlspecialchars($row['book_title']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['accession']); ?></td> <!-- Accession No -->
                                    <td class="text-center"><?php echo htmlspecialchars($row['type']); ?></td>
                                    <td class="text-center">₱<?php echo number_format($row['amount'], 2); ?></td>
                                    <td class="text-center"><?php echo date('Y-m-d', strtotime($row['issue_date'])); ?></td>
                                    <td class="text-center">
                                        <?php
                                        echo ($row['due_date'] !== null && $row['due_date'] !== '0000-00-00')
                                            ? date('Y-m-d', strtotime($row['due_date']))
                                            : '-';
                                        ?>
                                    </td>
                                    <td class="text-center"><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>


                                    <td class="text-center">
                                        <?php if ($row['status'] === 'Unpaid'): ?>
                                            <span class="badge badge-danger">Unpaid</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        echo ($row['payment_date'] !== null && $row['payment_date'] !== '0000-00-00')
                                            ? date('Y-m-d', strtotime($row['payment_date']))
                                            : '-';
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo ($row['invoice_sale'] !== null) ? htmlspecialchars($row['invoice_sale']) : '-'; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo ($row['reminder_sent'] == 1) ? '<span class="badge badge-success">Reminder Sent</span>' : '<span class="badge badge-warning">Not Reminded</span>'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="15" class="text-center text-muted">No records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Fines Statistics -->
        <div class="row mb-4">
            <!-- Total Unpaid Fines Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2 stats-card unpaid-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1 stats-title">
                                    Total Unpaid Fines</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $totalUnpaidFines; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-circle text-danger stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Paid Fines Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2 stats-card paid-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1 stats-title">
                                    Total Paid Fines</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $totalPaidFines; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle text-success stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Value of Unpaid Fines Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2 stats-card unpaid-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1 stats-title">
                                    Value of Unpaid Fines</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number">₱<?php echo number_format($totalUnpaidValue, 2); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-money-bill-wave text-danger stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Value of Paid Fines Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2 stats-card paid-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1 stats-title">
                                    Value of Paid Fines</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number">₱<?php echo number_format($totalPaidValue, 2); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-coins text-success stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('inc/footer.php'); ?>

<!-- Context Menu -->
<div class="context-menu" style="display: none; position: absolute; z-index: 1000;">
    <ul class="list-group">
        <li class="list-group-item" data-action="mark-paid">Mark as Paid</li>
        <li class="list-group-item" data-action="mark-unpaid">Mark as Unpaid</li>
    </ul>
</div>

<!-- Add SweetAlert2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

$(document).ready(function() {
    // Store references
    const contextMenu = $('.context-menu');
    let $selectedRow = null;

    // Right-click handler for table rows
    $('#finesTable tbody').on('contextmenu', 'tr', function(e) {
        e.preventDefault();

        $selectedRow = $(this);
        const status = $selectedRow.data('status');

        // Update context menu actions based on the fine's status
        contextMenu.find('li[data-action="mark-paid"]').toggle(status === 'Unpaid');
        contextMenu.find('li[data-action="mark-unpaid"]').toggle(status === 'Paid');

        // Show context menu at the cursor position
        contextMenu.css({
            top: e.pageY + "px",
            left: e.pageX + "px",
            display: "block"
        });
    });

    // Hide context menu on document click
    $(document).on('click', function() {
        contextMenu.hide();
    });

    // Prevent hiding when clicking menu items
    $('.context-menu').on('click', function(e) {
        e.stopPropagation();
    });

    $(".context-menu li").on('click', function() {
    const action = $(this).data('action');
    const selectedRows = $('.fineCheckbox:checked');
    const fineIds = selectedRows.map(function() {
        return $(this).val();
    }).get();

    console.log('Fine IDs:', fineIds); // Debug: Check fine IDs being sent

    if (action === 'mark-paid') {
    if (selectedRows.length === 0) {
        Swal.fire({
            title: 'Error',
            text: 'Please select at least one fine to mark as paid.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Collect borrower names and total amount
    const borrowerNames = [];
    let totalAmount = 0;
    selectedRows.each(function() {
        const borrower = $(this).closest('tr').data('borrower');
        const amount = parseFloat($(this).closest('tr').data('amount'));
        if (!borrowerNames.includes(borrower)) {
            borrowerNames.push(borrower);
        }
        totalAmount += amount;
    });

    if (borrowerNames.length > 1) {
        Swal.fire({
            title: 'Error',
            text: 'Cannot mark as paid for fines with different borrowers.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    const borrower = borrowerNames[0];

    Swal.fire({
        title: 'Confirm Payment',
        html: `
            <div class="text-left">
                <p class="mb-2"><strong>Borrower:</strong> ${borrower}</p>
                <p class="mb-2"><strong>Total Amount:</strong> ₱${totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>

                <div class="form-group mt-3">
                    <label for="payment_date" class="float-left"><strong>Payment Date:</strong></label>
                    <input type="date" id="payment_date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                </div>

                <div class="form-group mt-3">
                    <label for="invoice_sale" class="float-left"><strong>Invoice/OR Number:</strong></label>
                    <input type="text" id="invoice_sale" class="form-control" placeholder="Enter invoice or OR number">
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `<i class="fas fa-check"></i> Confirm Payment`,
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showLoaderOnConfirm: true,
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-danger'
        },
        preConfirm: () => {
            const paymentDate = document.getElementById('payment_date').value;
            const invoiceSale = document.getElementById('invoice_sale').value;

            // Validate inputs
            if (!paymentDate) {
                Swal.showValidationMessage('Payment date is required');
                return false;
            }
            if (!invoiceSale) {
                Swal.showValidationMessage('Invoice/OR number is required');
                return false;
            }

            return fetch('mark_fine_paid.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `fine_ids=${encodeURIComponent(JSON.stringify(fineIds))}&payment_date=${encodeURIComponent(paymentDate)}&invoice_sale=${encodeURIComponent(invoiceSale)}`
            })
            .then(response => {
                if (response.ok) {
                    // Redirect to the server endpoint to open the PDF in a new tab
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'mark_fine_paid.php';
                    form.target = '_blank';

                    // Add the required POST parameters
                    const fineIdsInput = document.createElement('input');
                    fineIdsInput.type = 'hidden';
                    fineIdsInput.name = 'fine_ids';
                    fineIdsInput.value = JSON.stringify(fineIds);
                    form.appendChild(fineIdsInput);

                    const paymentDateInput = document.createElement('input');
                    paymentDateInput.type = 'hidden';
                    paymentDateInput.name = 'payment_date';
                    paymentDateInput.value = paymentDate;
                    form.appendChild(paymentDateInput);

                    const invoiceSaleInput = document.createElement('input');
                    invoiceSaleInput.type = 'hidden';
                    invoiceSaleInput.name = 'invoice_sale';
                    invoiceSaleInput.value = invoiceSale;
                    form.appendChild(invoiceSaleInput);

                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                } else {
                    return response.json().then(err => { throw new Error(err.message); });
                }
            })
            .catch(error => {
                Swal.showValidationMessage(`Error: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Payment Recorded!',
                text: `The fines have been successfully marked as paid.`,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.reload();
            });
        }
    });
}
else if (action === 'mark-unpaid') {
    if (selectedRows.length === 0) {
        Swal.fire({
            title: 'Error',
            text: 'Please select at least one fine to mark as unpaid.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    Swal.fire({
        title: 'Confirm Action',
        text: 'Are you sure you want to mark the selected fines as unpaid?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `<i class="fas fa-check"></i> Yes, Mark as Unpaid`,
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch('mark_fine_unpaid.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `fine_ids=${encodeURIComponent(JSON.stringify(fineIds))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'error') {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Error: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Status Updated!',
                text: `The fines have been successfully marked as unpaid.`,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.reload();
            });
        }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Status Updated!',
                    text: `The fines have been successfully marked as unpaid.`,
                    confirmButtonColor: '#28a745',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }

    contextMenu.hide();
});

    // Add custom styles for the context menu
    $('<style>')
        .text(`
            .context-menu {
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
            }
            .context-menu .list-group-item {
                cursor: pointer;
                padding: 8px 20px;
            }
            .context-menu .list-group-item:hover {
                background-color: #f8f9fa;
            }
            tr[data-fine-id] {
                cursor: context-menu;
            }
        `)
        .appendTo('head');
});
</script>


<!-- generate receipt script -->
<script>
$(document).ready(function () {
    $('#generateReceiptBtn').on('click', function () {
        const selectedRows = $('.fineCheckbox:checked');
        if (selectedRows.length === 0) {
            Swal.fire({
                title: 'Error',
                text: 'Please select at least one fine to generate a receipt.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Check if all selected rows have the status "Unpaid"
        let hasInvalidStatus = false;
        selectedRows.each(function () {
            const row = $(this).closest('tr');
            const status = row.find('td:nth-child(11)').text().trim(); // Status column
            if (status !== 'Unpaid') {
                hasInvalidStatus = true;
                return false; // Break the loop
            }
        });

        if (hasInvalidStatus) {
            Swal.fire({
                title: 'Error',
                text: 'Only fines with the status "Unpaid" can be generated for a temporary receipt.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Collect borrower details
        const firstRow = selectedRows.first().closest('tr');
        const borrowerName = firstRow.find('td:nth-child(3)').text().trim(); // Borrower Name column
        const schoolId = firstRow.find('td:nth-child(2)').text().trim(); // Borrower ID column
        const department = firstRow.data('course') || 'N/A'; // Get course from data attribute
        const usertype = firstRow.data('user-type') || 'N/A'; // Get usertype from data attribute

        // Format borrower info
        const borrowerInfo = `
            <strong> Borrower Details</strong> <br>
            <strong>ID Number:</strong> ${schoolId} <span class="float-right"><strong>User Type:</strong> ${usertype}</span><br>
            <strong>Name:</strong> ${borrowerName} <span class="float-right"><strong>Course:</strong> ${department}</span>
        `;

        let totalAmount = 0;
        let fineDetails = `
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Accession</th>
                        <th>Fine Type</th>
                        <th>Status</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
        `;

        selectedRows.each(function () {
            const row = $(this).closest('tr');
            let bookTitle = row.find('td:nth-child(4)').text().trim(); // Book Title column
            const accession = row.find('td:nth-child(5)').text().trim(); // Accession column
            const fineType = row.find('td:nth-child(6)').text().trim(); // Fine Type column
            const status = row.find('td:nth-child(11)').text().trim(); // Status column
            const amountText = row.find('td:nth-child(7)').text().replace('₱', '').replace(',', '').trim(); // Fine Amount column
            const amount = parseFloat(amountText) || 0; // Parse amount or default to 0 if invalid
            totalAmount += amount;

            // Limit the book title to 50 characters
            if (bookTitle.length > 50) {
                bookTitle = bookTitle.substring(0, 50) + '...';
            }

            fineDetails += `
                <tr>
                    <td>${bookTitle}</td>
                    <td>${accession}</td>
                    <td>${fineType}</td>
                    <td>${status}</td>
                    <td>₱${amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                </tr>
            `;
        });

        fineDetails += `
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Total:</strong></td>
                        <td><strong>₱${totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong></td>
                    </tr>
                </tfoot>
            </table>
        `;

        // Confirm with the user before generating the receipt
        Swal.fire({
            title: 'Generate Temporary Receipt',
            html: `
                <div class="text-left">
                    <p>${borrowerInfo}</p>
                    ${fineDetails}
                </div>
            `,
            imageUrl: '/Library-System/Admin/inc/img/horizontal-nbs-logo.png',
            imageWidth: 300, // Adjust the width of the image
            imageHeight: 150, // Adjust the height of the image
            imageAlt: 'Receipt Icon', // Alt text for the image
            showCancelButton: true,
            confirmButtonText: 'Generate Receipt',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            customClass: {
                popup: 'swal-wide' // Add a custom class to the popup
            },
            width: 'auto', // Dynamically adjust the width based on content
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to fine-receipt.php with the selected fine IDs
                const form = $('<form>', {
                    method: 'POST',
                    action: 'fine-receipt.php',
                    target: '_blank'
                });

                selectedRows.each(function () {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'fine_ids[]',
                        value: $(this).val()
                    }));
                });

                $('body').append(form);
                form.submit();
            }
        });
    });




    // Handle "Select All" checkbox
    $('#selectAll').on('change', function() {
        $('.fineCheckbox').prop('checked', $(this).prop('checked'));
    });

    // Uncheck "Select All" if any individual checkbox is unchecked
    $('.fineCheckbox').on('change', function() {
        if (!$(this).prop('checked')) {
            $('#selectAll').prop('checked', false);
        }
    });
});


// SCRIPT FOR FILTERS AND RESET
$(document).ready(function () {
    // Handle "Toggle Filter" button click
    $('#toggleFilter').on('click', function () {
        const filterForm = $('#filterForm');
        filterForm.toggleClass('d-none'); // Toggle visibility by adding/removing the 'd-none' class

        // Update the button text/icon based on the visibility of the filter form
        if (filterForm.hasClass('d-none')) {
            $(this).html('<i class="fas fa-filter"></i> Toggle Filter');
        } else {
            $(this).html('<i class="fas fa-filter"></i> Hide Filter');
        }
    });
});

$(document).ready(function () {
    // Handle "Reset Filters" button click
    $('#resetFilters').on('click', function () {
        // Clear all filter inputs
        $('#finesFilterForm').find('input, select').each(function () {
            $(this).val(''); // Reset input and select values
        });

        // Reload the page without query parameters
        window.location.href = window.location.pathname;
    });
});



// script for export paid fines
$(document).ready(function () {
    // Handle "Export Paid Fines" button click
    $('#exportPaidFinesBtn').on('click', function () {
        Swal.fire({
            title: 'Export Paid Fines',
            text: 'Are you sure you want to export all paid fines?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Export',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'export_fines.php?status=Paid';
            }
        });
    });
});

// export for export unpaid fines
$(document).ready(function () {
    // Handle "Export Unpaid Fines" button click
    $('#exportUnpaidFinesBtn').on('click', function () {
        Swal.fire({
            title: 'Export Unpaid Fines',
            text: 'Are you sure you want to export all unpaid fines?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Export',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'export_fines.php?status=Unpaid';

            }
        });
    });
});


$(document).ready(function () {
    // Handle "Remind All" button click
    $('#remindAllBtn').on('click', function () {
        Swal.fire({
            title: 'Send Reminders',
            text: 'Are you sure you want to send reminders for all unpaid fines?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Send Reminders',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('send_fine_reminders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remind_all`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'error') {
                        throw new Error(data.message);
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Error: ${error.message}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Reminders Sent!',
                    text: 'Reminders have been successfully sent for all unpaid fines.',
                    confirmButtonColor: '#28a745',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Refresh the page after the success message
                    window.location.reload();
                });
            }
        });
    });
});




</script>
