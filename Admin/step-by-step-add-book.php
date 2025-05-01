<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Check for success flag from form submission
$show_success_message = false;
if (isset($_SESSION['book_shortcut_success']) && $_SESSION['book_shortcut_success']) {
    $show_success_message = true;
    $success_alert_message = $_SESSION['success_message'] ?? 'Book added successfully!'; // Use the message from the form if set
    // Clear the flags immediately after reading them
    unset($_SESSION['book_shortcut_success']);
    unset($_SESSION['success_message']);
    // Ensure the shortcut data is cleared if success flag was set
    unset($_SESSION['book_shortcut']);
}

// Reset the return_to_form flag since we're on the main progress page
$_SESSION['return_to_form'] = false;

// Handle reset progress action (also used by "Add Another Book")
if (isset($_POST['reset_progress'])) {
    // Reset the book shortcut session data
    $_SESSION['book_shortcut'] = [
        'current_step' => 1,
        'selected_writers' => [],
        'publisher_id' => null,
        'publish_year' => null,
        'book_title' => '',
        'steps_completed' => [
            'writer' => false,
            'publisher' => false,
            'title' => false
        ]
    ];
    // Clear any lingering success flags if reset is clicked
    unset($_SESSION['book_shortcut_success']);
    unset($_SESSION['success_message']);

    // Redirect to prevent form resubmission
    header("Location: step-by-step-add-book.php");
    exit();
}

// Initialize the progress session if not already set AND success message is not being shown
// If success message is shown, we don't initialize until "Add Another Book" is clicked
if (!isset($_SESSION['book_shortcut']) && !$show_success_message) {
    $_SESSION['book_shortcut'] = [
        'current_step' => 1,
        'selected_writers' => [],
        'publisher_id' => null,
        'publish_year' => null,
        'book_title' => '',
        'steps_completed' => [
            'writer' => false,
            'publisher' => false,
            'title' => false
        ]
    ];
}

// Determine the active step based on completion status if not explicitly set
// Only run this if shortcut data exists (i.e., not immediately after success)
if (isset($_SESSION['book_shortcut'])) {
    if (!isset($_POST['step'])) {
        if (!$_SESSION['book_shortcut']['steps_completed']['writer']) {
            $_SESSION['book_shortcut']['current_step'] = 1;
        } elseif (!$_SESSION['book_shortcut']['steps_completed']['publisher']) {
            $_SESSION['book_shortcut']['current_step'] = 2;
        } elseif (!$_SESSION['book_shortcut']['steps_completed']['title']) {
            $_SESSION['book_shortcut']['current_step'] = 3;
        } else {
            // If all steps were somehow completed but form wasn't reached, default to step 3
            $_SESSION['book_shortcut']['current_step'] = 3;
        }
    } else {
        $_SESSION['book_shortcut']['current_step'] = (int)$_POST['step'];
    }

    $current_step = $_SESSION['book_shortcut']['current_step'];
    $steps_completed = $_SESSION['book_shortcut']['steps_completed'];
} else {
    // Default values if shortcut is not set (e.g., after success)
    $current_step = 1;
    $steps_completed = [
        'writer' => false,
        'publisher' => false,
        'title' => false
    ];
}


// Include header AFTER all potential redirects and session checks
include 'inc/header.php';

// Helper function to determine tab class
function get_tab_class($step_num, $current_step, $is_completed) {
    $class = 'nav-link';
    // Disable all tabs if success message is shown
    if (isset($GLOBALS['show_success_message']) && $GLOBALS['show_success_message']) {
        return $class . ' disabled';
    }
    if ($step_num == $current_step) {
        $class .= ' active';
    }
    if ($is_completed) {
        $class .= ' completed';
    }
    // Check dependencies based on session data if it exists
    if (isset($_SESSION['book_shortcut'])) {
        if ($step_num == 2 && !$_SESSION['book_shortcut']['steps_completed']['writer']) {
            $class .= ' disabled';
        }
        if ($step_num == 3 && (!$_SESSION['book_shortcut']['steps_completed']['writer'] || !$_SESSION['book_shortcut']['steps_completed']['publisher'])) {
            $class .= ' disabled';
        }
    } else {
        // If no session data, disable steps 2 and 3
        if ($step_num > 1) {
            $class .= ' disabled';
        }
    }
    return $class;
}

// Helper function to determine tab pane class
function get_pane_class($step_num, $current_step) {
    // If success message is shown, no panes should be active
    if (isset($GLOBALS['show_success_message']) && $GLOBALS['show_success_message']) {
        return 'tab-pane fade';
    }
    return $step_num == $current_step ? 'tab-pane fade show active' : 'tab-pane fade';
}
?>

<!-- Main Content -->
<div id="content">
    <div class="container-fluid">

        <!-- Display Success Message if applicable -->
        <?php if ($show_success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h4 class="alert-heading"><i class="fas fa-check-circle"></i> All Done!</h4>
            <p><?php echo htmlspecialchars($success_alert_message); ?></p>
            <hr>
            <p class="mb-0">You can now add another book or view the list of books.</p>
            <div class="mt-2">
                <form method="post" class="d-inline-block mr-2">
                    <button type="submit" name="reset_progress" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Another Book
                    </button>
                </form>
                <a href="book_list.php" class="btn btn-info d-inline-block">
                    <i class="fas fa-list"></i> View Inserted Books
                </a>
            </div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>


        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                <h6 class="m-0 font-weight-bold text-primary mb-2 mb-sm-0">Add Book Shortcut</h6>
                <!-- Reset Progress Button - Hide if success message is shown -->
                <?php if (!$show_success_message): ?>
                <form method="post" onsubmit="return confirmReset()">
                    <button type="submit" name="reset_progress" class="btn btn-danger btn-sm">
                        <i class="fas fa-redo"></i> Reset Progress
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <!-- Progress Bar -->
                <?php
                    // Calculate progress only if shortcut data exists
                    $progress_percent = 0;
                    if (isset($_SESSION['book_shortcut'])) {
                        if ($steps_completed['writer']) $progress_percent += 33.33;
                        if ($steps_completed['publisher']) $progress_percent += 33.33;
                        if ($steps_completed['title']) $progress_percent += 33.33;
                        $progress_percent = min(100, round($progress_percent, 2));
                    }
                ?>
                <div class="progress mb-4">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percent; ?>%"
                         aria-valuenow="<?php echo $progress_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                         <?php echo $progress_percent; ?>% Complete
                    </div>
                </div>

                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4" id="addBookTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="<?php echo get_tab_class(1, $current_step, $steps_completed['writer']); ?>"
                           id="step1-tab" data-toggle="tab" href="#step1" role="tab" aria-controls="step1"
                           aria-selected="<?php echo $current_step == 1 && !$show_success_message ? 'true' : 'false'; ?>">
                           <span class="step-number">1</span> Check/Add Writer <?php if ($steps_completed['writer']) echo '<i class="fas fa-check text-success ml-1"></i>'; ?>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="<?php echo get_tab_class(2, $current_step, $steps_completed['publisher']); ?>"
                           id="step2-tab" data-toggle="tab" href="#step2" role="tab" aria-controls="step2"
                           aria-selected="<?php echo $current_step == 2 && !$show_success_message ? 'true' : 'false'; ?>">
                           <span class="step-number">2</span> Check/Add Publisher <?php if ($steps_completed['publisher']) echo '<i class="fas fa-check text-success ml-1"></i>'; ?>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="<?php echo get_tab_class(3, $current_step, $steps_completed['title']); ?>"
                           id="step3-tab" data-toggle="tab" href="#step3" role="tab" aria-controls="step3"
                           aria-selected="<?php echo $current_step == 3 && !$show_success_message ? 'true' : 'false'; ?>">
                           <span class="step-number">3</span> Book Details & Add <?php if ($steps_completed['title']) echo '<i class="fas fa-check text-success ml-1"></i>'; ?>
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="addBookTabsContent">
                    <!-- Step 1 Content -->
                    <div class="<?php echo get_pane_class(1, $current_step); ?>" id="step1" role="tabpanel" aria-labelledby="step1-tab">
                        <div class="text-center mb-4">
                            <h4>Step 1: Check/Add Writer(s)</h4>
                            <p>Check if the writer(s) already exist in the system or add new ones. Select the primary author and any co-authors/contributors.</p>
                            <!-- Disable button if success message is shown -->
                            <a href="step-by-step-writers.php" class="btn btn-primary <?php echo $show_success_message ? 'disabled' : ''; ?>"
                               <?php echo $show_success_message ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                               Manage Writers
                            </a>
                        </div>
                    </div>
                    <!-- Step 2 Content -->
                    <div class="<?php echo get_pane_class(2, $current_step); ?>" id="step2" role="tabpanel" aria-labelledby="step2-tab">
                        <div class="text-center mb-4">
                            <h4>Step 2: Check/Add Publisher</h4>
                            <p>Check if the publisher already exists in the system or add a new publisher. Select the publisher and enter the publication year.</p>
                            <!-- Disable button if success message is shown or step 1 not complete -->
                            <a href="step-by-step-publishers.php" class="btn btn-primary <?php echo ($show_success_message || !$steps_completed['writer']) ? 'disabled' : ''; ?>"
                               <?php echo ($show_success_message || !$steps_completed['writer']) ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                               Manage Publishers
                            </a>
                             <?php if (!$steps_completed['writer'] && !$show_success_message): ?>
                                <small class="d-block text-muted mt-2">Please complete Step 1 first.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Step 3 Content -->
                    <div class="<?php echo get_pane_class(3, $current_step); ?>" id="step3" role="tabpanel" aria-labelledby="step3-tab">
                        <div class="text-center mb-4">
                            <h4>Step 3: Book Title Check & Add Book</h4>
                            <p>Check if the book title already exists or proceed to add the new book with all collected details.</p>
                             <!-- Disable button if success message is shown or step 2 not complete -->
                             <a href="step-by-step-books.php" class="btn btn-primary <?php echo ($show_success_message || !$steps_completed['publisher']) ? 'disabled' : ''; ?>"
                                <?php echo ($show_success_message || !$steps_completed['publisher']) ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                                Check/Enter Book Title
                             </a>
                             <?php if (!$steps_completed['publisher'] && !$show_success_message): ?>
                                <small class="d-block text-muted mt-2">Please complete Step 2 first.</small>
                            <?php endif; ?>

                             <?php if ($steps_completed['writer'] && $steps_completed['publisher'] && $steps_completed['title'] && !$show_success_message): ?>
                                <div class="mt-3">
                                    <p class="text-success font-weight-bold">All preliminary steps completed!</p>
                                    <a href="step-by-step-add-book-form.php" class="btn btn-success">
                                        <i class="fas fa-pencil-alt"></i> Proceed to Fill Full Book Details
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Progress Summary - Hide if success message is shown -->
                <?php if (!$show_success_message && isset($_SESSION['book_shortcut'])): ?>
                <div class="mt-4 p-3 bg-light rounded">
                    <h6>Progress Summary:</h6>
                    <ul class="list-unstyled summary-list">
                        <li class="mb-2">Writer(s):
                            <?php
                            if (!empty($_SESSION['book_shortcut']['selected_writers'])) {
                                $writer_names = [];
                                foreach ($_SESSION['book_shortcut']['selected_writers'] as $selected_writer) {
                                    $writer_id = $selected_writer['id'];
                                    $writer_role = $selected_writer['role'];
                                    $stmt = $conn->prepare("SELECT firstname, middle_init, lastname FROM writers WHERE id = ?");
                                    $stmt->bind_param("i", $writer_id);
                                    $stmt->execute();
                                    $writer_result = $stmt->get_result();

                                    if ($writer_result && $writer_result->num_rows > 0) {
                                        $writer = $writer_result->fetch_assoc();
                                        $writer_names[] = '<span class="badge badge-'.($writer_role == 'Author' ? 'primary' : ($writer_role == 'Co-Author' ? 'info' : 'secondary')) .
                                            '">' . htmlspecialchars($writer_role) . '</span> ' .
                                            htmlspecialchars(trim($writer['firstname'] . ' ' . $writer['middle_init'] . ' ' . $writer['lastname']));
                                    }
                                    $stmt->close();
                                }
                                echo implode('<br>', $writer_names);
                            } else {
                                echo '<span class="text-warning">Not selected</span>';
                            }
                            ?>
                        </li>
                        <li class="mb-2">Publisher:
                            <?php
                            if ($_SESSION['book_shortcut']['publisher_id']) {
                                $publisher_id = $_SESSION['book_shortcut']['publisher_id'];
                                $stmt = $conn->prepare("SELECT publisher, place FROM publishers WHERE id = ?");
                                $stmt->bind_param("i", $publisher_id);
                                $stmt->execute();
                                $publisher_result = $stmt->get_result();

                                if ($publisher_result && $publisher_result->num_rows > 0) {
                                    $publisher = $publisher_result->fetch_assoc();
                                    $publish_year = isset($_SESSION['book_shortcut']['publish_year']) && $_SESSION['book_shortcut']['publish_year'] ?
                                        $_SESSION['book_shortcut']['publish_year'] : '<em class="text-muted">Year not set</em>';
                                    echo '<span class="text-success">' .
                                        htmlspecialchars($publisher['publisher']) .
                                        ' (' . htmlspecialchars($publisher['place']) .
                                        ', ' . htmlspecialchars($publish_year) . ')</span>';
                                } else {
                                    echo '<span class="text-danger">Error: Publisher not found</span>';
                                }
                                 $stmt->close();
                            } else {
                                echo '<span class="text-warning">Not selected</span>';
                            }
                            ?>
                        </li>
                        <li>Book Title:
                            <?php
                            echo $_SESSION['book_shortcut']['book_title'] ?
                                '<span class="text-success">' . htmlspecialchars($_SESSION['book_shortcut']['book_title']) . '</span>' :
                                '<span class="text-warning">Not entered</span>';
                            ?>
                        </li>
                     </ul>
                     <?php if ($steps_completed['writer'] && $steps_completed['publisher'] && $steps_completed['title']): ?>
                         <div class="mt-3 text-center">
                             <a href="step-by-step-add-book-form.php" class="btn btn-success">
                                 <i class="fas fa-pencil-alt"></i> Proceed to Fill Full Book Details
                             </a>
                         </div>
                     <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* ... existing styles ... */

/* Style for disabled tabs when success message is shown */
.nav-tabs .nav-link.disabled {
    color: #b8bdc3;
    background-color: transparent;
    border-color: transparent transparent #dee2e6;
    cursor: not-allowed;
}

.nav-tabs .nav-link.disabled .step-number {
    background-color: #f8f9fa;
    color: #b8bdc3;
    border-color: #dee2e6;
}

/* Ensure disabled links are not clickable */
.nav-tabs .nav-link.disabled {
    pointer-events: none;
}

/* ... existing styles ... */
</style>

<script>
function confirmReset() {
    return confirm('Are you sure you want to reset your progress? All selected items will be cleared.');
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle disabled tabs - prevent clicking
    var disabledTabs = document.querySelectorAll('.nav-tabs .nav-link.disabled');
    disabledTabs.forEach(function(tab) {
        tab.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
        });
        // Ensure they don't appear active even if they have the class
        tab.classList.remove('active');
        var targetPaneId = tab.getAttribute('href');
        if (targetPaneId) {
            var targetPane = document.querySelector(targetPaneId);
            if (targetPane) {
                targetPane.classList.remove('show', 'active');
            }
        }
    });

    // Activate the correct tab if not showing success message
    var showSuccess = <?php echo json_encode($show_success_message); ?>;
    if (!showSuccess) {
        var activeTab = document.querySelector('.nav-tabs .nav-link.active:not(.disabled)');
        if (activeTab) {
            if (typeof $ !== 'undefined' && typeof $.fn.tab !== 'undefined') {
                 $(activeTab).tab('show');
            } else {
                var targetPaneId = activeTab.getAttribute('href');
                var targetPane = document.querySelector(targetPaneId);
                if (targetPane) {
                     document.querySelectorAll('.tab-content .tab-pane').forEach(function(pane){
                         pane.classList.remove('show', 'active');
                     });
                     targetPane.classList.add('show', 'active');
                }
            }
        } else {
            // If no active tab found (e.g., after reset), default to step 1 if possible
            var step1Tab = document.getElementById('step1-tab');
            if (step1Tab && !step1Tab.classList.contains('disabled')) {
                 if (typeof $ !== 'undefined' && typeof $.fn.tab !== 'undefined') {
                     $(step1Tab).tab('show');
                 } else {
                     var targetPane = document.querySelector('#step1');
                     if (targetPane) {
                         document.querySelectorAll('.tab-content .tab-pane').forEach(function(pane){
                             pane.classList.remove('show', 'active');
                         });
                         targetPane.classList.add('show', 'active');
                     }
                 }
            }
        }
    } else {
         // If success message is shown, ensure no tabs are active visually
         document.querySelectorAll('.nav-tabs .nav-link').forEach(tab => tab.classList.remove('active'));
         document.querySelectorAll('.tab-content .tab-pane').forEach(pane => pane.classList.remove('show', 'active'));
    }
});
</script>

<?php include 'inc/footer.php'; ?>
