<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Reset the return_to_form flag since we're on the main progress page
$_SESSION['return_to_form'] = false;

// Handle reset progress action
if (isset($_POST['reset_progress'])) {
    // Reset the book shortcut session data
    $_SESSION['book_shortcut'] = [
        'current_step' => 1,
        'writer_id' => null,
        'publisher_id' => null,
        'book_title' => '',
        'steps_completed' => [
            'writer' => false,
            'publisher' => false,
            'title' => false
        ]
    ];
    
    // Redirect to prevent form resubmission
    header("Location: step-by-step-add-book.php");
    exit();
}

// Initialize the progress session if not already set
if (!isset($_SESSION['book_shortcut'])) {
    $_SESSION['book_shortcut'] = [
        'current_step' => 1,
        'writer_id' => null,
        'publisher_id' => null,
        'book_title' => '',
        'steps_completed' => [
            'writer' => false,
            'publisher' => false,
            'title' => false
        ]
    ];
}

// Handle step navigation
if (isset($_POST['step'])) {
    $_SESSION['book_shortcut']['current_step'] = (int)$_POST['step'];
}

$current_step = $_SESSION['book_shortcut']['current_step'];

// Include header AFTER all potential redirects
include 'inc/header.php';
?>

<!-- Main Content -->
<div id="content">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                <h6 class="m-0 font-weight-bold text-primary mb-2 mb-sm-0">Add Book Shortcut</h6>
                <!-- Reset Progress Button -->
                <form method="post" onsubmit="return confirmReset()">
                    <button type="submit" name="reset_progress" class="btn btn-danger btn-sm">
                        <i class="fas fa-redo"></i> Reset Progress
                    </button>
                </form>
            </div>
            <div class="card-body">
                <!-- Progress Bar -->
                <div class="progress mb-4">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo ($current_step - 1) * 33.33; ?>%"
                         aria-valuenow="<?php echo ($current_step - 1) * 33.33; ?>" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>

                <!-- Step Indicators - Improve for mobile -->
                <div class="row mb-4 text-center">
                    <div class="col-6 col-md-4 mb-3 mb-md-0">
                        <div class="step <?php echo $current_step >= 1 ? 'active' : ''; ?> <?php echo $_SESSION['book_shortcut']['steps_completed']['writer'] ? 'completed' : ''; ?>">
                            <div class="step-icon">1</div>
                            <div class="step-text">Check/Add Writer</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 mb-3 mb-md-0">
                        <div class="step <?php echo $current_step >= 2 ? 'active' : ''; ?> <?php echo $_SESSION['book_shortcut']['steps_completed']['publisher'] ? 'completed' : ''; ?>">
                            <div class="step-icon">2</div>
                            <div class="step-text">Check/Add Publisher</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="step <?php echo $current_step >= 3 ? 'active' : ''; ?> <?php echo $_SESSION['book_shortcut']['steps_completed']['title'] ? 'completed' : ''; ?>">
                            <div class="step-icon">3</div>
                            <div class="step-text">Book Details & Add</div>
                        </div>
                    </div>
                </div>

                <!-- Current Step Content -->
                <div class="step-content">
                    <?php if ($current_step == 1): ?>
                        <div class="text-center mb-4">
                            <h4>Step 1: Check/Add Writer</h4>
                            <p>Check if the writer already exists in the system or add a new writer.</p>
                            <a href="step-by-step-writers.php" class="btn btn-primary">Continue to Writers</a>
                        </div>
                    <?php elseif ($current_step == 2): ?>
                        <div class="text-center mb-4">
                            <h4>Step 2: Check/Add Publisher</h4>
                            <p>Check if the publisher already exists in the system or add a new publisher.</p>
                            <a href="step-by-step-publishers.php" class="btn btn-primary">Continue to Publishers</a>
                        </div>
                    <?php elseif ($current_step == 3): ?>
                        <div class="text-center mb-4">
                            <h4>Step 3: Book Title Check & Add Book</h4>
                            <p>Check if the book title already exists or proceed to add a new book.</p>
                            <a href="step-by-step-books.php" class="btn btn-primary">Continue to Books</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Step Navigation -->
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <?php if ($current_step > 1): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="step" value="<?php echo $current_step - 1; ?>">
                                <button type="submit" class="btn btn-secondary mr-2 mb-2 mb-sm-0">Previous Step</button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($current_step == 3 && $_SESSION['book_shortcut']['steps_completed']['title']): ?>
                            <a href="step-by-step-add-book-form.php" class="btn btn-success mr-2 mb-2 mb-sm-0">
                                <i class="fas fa-pencil-alt"></i> Fill Book Details
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($current_step < 3): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="step" value="<?php echo $current_step + 1; ?>">
                                <button type="submit" class="btn btn-primary">Next Step</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progress Summary - Improve for mobile -->
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
                                    $writer_query = "SELECT firstname, middle_init, lastname FROM writers WHERE id = $writer_id";
                                    $writer_result = $conn->query($writer_query);
                                    
                                    if ($writer_result && $writer_result->num_rows > 0) {
                                        $writer = $writer_result->fetch_assoc();
                                        $writer_names[] = '<span class="badge badge-'.($writer_role == 'Author' ? 'primary' : ($writer_role == 'Co-Author' ? 'info' : 'secondary')) . 
                                            '">' . htmlspecialchars($writer_role) . '</span> ' . 
                                            htmlspecialchars($writer['firstname'] . ' ' . $writer['middle_init'] . ' ' . $writer['lastname']);
                                    }
                                }
                                echo implode(', ', $writer_names);
                            } 
                            // Fallback for older format
                            elseif (isset($_SESSION['book_shortcut']['writer_id']) && $_SESSION['book_shortcut']['writer_id']) {
                                $writer_id = $_SESSION['book_shortcut']['writer_id'];
                                $writer_query = "SELECT firstname, middle_init, lastname FROM writers WHERE id = $writer_id";
                                $writer_result = $conn->query($writer_query);
                                if ($writer_result && $writer_result->num_rows > 0) {
                                    $writer = $writer_result->fetch_assoc();
                                    echo '<span class="text-success">' . htmlspecialchars($writer['firstname']) . ' ' . 
                                         htmlspecialchars($writer['middle_init']) . ' ' . 
                                         htmlspecialchars($writer['lastname']) . '</span>';
                                }
                            } else {
                                echo '<span class="text-warning">Not selected</span>';
                            }
                            ?>
                        </li>
                        <li class="mb-2">Publisher: 
                            <?php 
                            if ($_SESSION['book_shortcut']['publisher_id']) {
                                $publisher_id = $_SESSION['book_shortcut']['publisher_id'];
                                $publisher_query = "SELECT publisher, place FROM publishers WHERE id = $publisher_id";
                                $publisher_result = $conn->query($publisher_query);
                                if ($publisher_result && $publisher_result->num_rows > 0) {
                                    $publisher = $publisher_result->fetch_assoc();
                                    $publish_year = isset($_SESSION['book_shortcut']['publish_year']) ? 
                                        $_SESSION['book_shortcut']['publish_year'] : date('Y');
                                    echo '<span class="text-success">' . 
                                        htmlspecialchars($publisher['publisher']) . 
                                        ' (' . htmlspecialchars($publisher['place']) . 
                                        ', ' . htmlspecialchars($publish_year) . ')</span>';
                                } else {
                                    echo '<span class="text-warning">Publisher not found</span>';
                                }
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
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.step {
    position: relative;
    padding: 10px;
}

.step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 auto 10px;
}

.step.active .step-icon {
    background-color: #4e73df;
    color: white;
}

.step.completed .step-icon {
    background-color: #1cc88a;
    color: white;
}

.step.completed .step-icon:after {
    content: '✓';
    position: absolute;
    top: 5px;
    right: 50%;
    font-size: 12px;
}

/* Add responsive styles */
@media (max-width: 767px) {
    .summary-list li {
        word-break: break-word;
    }
    
    .badge {
        display: inline-block;
        margin-bottom: 3px;
    }
    
    .step {
        padding: 5px;
        margin-bottom: 15px;
    }
    
    .step-text {
        font-size: 0.9rem;
    }
}

/* Ensure badges wrap properly */
.badge {
    white-space: normal;
    text-align: left;
}

/* Make progress summary more readable on mobile */
@media (max-width: 576px) {
    .summary-list {
        padding-left: 0;
    }
    
    .summary-list li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
}
</style>

<script>
// Confirmation before resetting progress
function confirmReset() {
    return confirm('Are you sure you want to reset your progress? All selected items will be cleared.');
}
</script>

<?php include 'inc/footer.php'; ?>
