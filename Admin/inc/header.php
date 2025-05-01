<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <link rel="icon" type="image/x-icon" href="img/nbslogo.png">

    <title>NBS College Library</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="inc/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="inc/assets/DataTables/datatables.min.css" rel="stylesheet">

    <!-- Add these before closing head tag -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/responsive-fixes.css">
    
    <style>
        /* Custom icon colors for sidebar */
        .icon-dashboard { color: #4e73df; } /* Default blue */
        .icon-reports { color: #1cc88a; } /* Green */
        .icon-admin { color: #f6c23e; } /* Yellow/Gold */
        .icon-book { color: #e74a3b; } /* Red */
        .icon-borrow { color: #36b9cc; } /* Light blue/cyan */
        .icon-gray { color: #666769; } /* Gray as requested */
        
        /* Active menu item styling */
        .nav-item.active-page > .nav-link {
            background-color: rgba(255, 255, 255, 0.15);
            font-weight: bold;
        }
        
        /* Page indicator styling */
        #pageIndicator {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(78, 115, 223, 0.9);
            color: white;
            padding: 5px 15px;
            border-radius: 0 0 8px 8px;
            z-index: 1050;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            font-size: 0.8rem;
            transition: all 0.3s ease;
            display: none;
        }
    </style>
</head>

<body id="page-top">
    <?php
    // Get current page filename
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    // Function to get page title based on filename
    function getPageTitle($filename) {
        switch($filename) {
            case 'dashboard.php': return 'Dashboard';
            case 'reports.php': return 'Library Reports';
            case 'admins_list.php': return 'Admin Accounts';
            case 'users_list.php': return 'Library Users';
            case 'book_list.php': return 'Books Catalog';
            case 'writers_list.php': return 'Authors & Writers';
            case 'publisher_list.php': return 'Publishers';
            case 'publications_list.php': return 'Book Publications';
            case 'contributors_list.php': return 'Book Contributors';
            case 'book_borrowing.php': return 'Walk-in Book Borrowing';
            case 'book_reservations.php': return 'Online Book Reservations';
            case 'borrowed_books.php': return 'Issued Books';
            case 'borrowing_history.php': return 'Borrowing History';
            case 'fines.php': return 'Fines Management';
            case 'lost_books.php': return 'Lost Book Records';
            case 'damaged_books.php': return 'Damaged Book Records';
            case 'profile.php': return 'My Profile';
            default: return 'NBS College Library System';
        }
    }
    
    $pageTitle = getPageTitle($currentPage);
    
    // Role-based access control for pages
    function checkPageAccess($page, $role) {
        // Pages accessible to encoders (book management only)
        $encoder_pages = ['dashboard.php', 'book_list.php', 'writers_list.php', 
                         'publisher_list.php', 'publications_list.php', 'contributors_list.php'];
        
        // Pages accessible to librarians and assistants (everything except admin users)
        $librarian_pages = array_merge($encoder_pages, [
            'users_list.php', 'book_borrowing.php', 'book_reservations.php',
            'borrowed_books.php', 'borrowing_history.php', 'fines.php',
            'lost_books.php', 'damaged_books.php', 'profile.php', 'messages.php'
        ]);
        
        // Check access based on role
        if ($role === 'Admin') {
            return true; // Admins can access all pages
        } else if (($role === 'Librarian' || $role === 'Assistant') && in_array($page, $librarian_pages)) {
            return true;
        } else if ($role === 'Encoder' && in_array($page, $encoder_pages)) {
            return true;
        }
        
        return false;
    }
    
    // Redirect if user doesn't have access to this page
    if (!checkPageAccess($currentPage, $_SESSION['role'])) {
        header('Location: dashboard.php');
        exit();
    }
    ?>
    
    <!-- Page Indicator -->
    <div id="pageIndicator">You are in: <?php echo $pageTitle; ?></div>
    
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php 
                $base_url = '/Library-System/Admin/';
                echo $base_url . 'dashboard.php'; 
            ?>">
                <div class="sidebar-brand-icon rotate-n-15">
                    <!-- <img src="img/nbs-icon.png" alt="Library Logo" width="30" height="50"> -->
                </div>
                <div class="sidebar-brand-text mx-3">NBSC Library <sup></sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item <?php echo $currentPage == 'dashboard.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt icon-dashboard"></i>
                    <span>Dashboard</span></a>
            </li>

            <?php if($_SESSION['role'] === 'Admin'): ?>
            <!-- Reports menu item - Admin only -->
            <li class="nav-item <?php echo $currentPage == 'reports.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="reports.php">
                    <i class="fas fa-chart-bar icon-reports"></i>
                    <span>Library Reports</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Admin Operations Section -->
            <?php if($_SESSION['role'] !== 'Encoder'): ?>
            <!-- Heading -->
            <div class="sidebar-heading">
                Admin Operations
            </div>

            <?php if($_SESSION['role'] === 'Admin'): ?>
            <!-- User management items - Admin only -->
            <li class="nav-item <?php echo $currentPage == 'admins_list.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="admins_list.php">
                    <i class="fas fa-user-shield icon-admin"></i>
                    <span>Admin Accounts</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Users management - Admin, Librarian, Assistant -->
            <li class="nav-item <?php echo $currentPage == 'users_list.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="users_list.php">
                    <i class="fas fa-users icon-admin"></i>
                    <span>Library Users</span>
                </a>
            </li>
            
            <!-- Divider -->
            <hr class="sidebar-divider">
            <?php endif; ?>

            <!-- Book Management Section - Available to all roles -->
            <!-- Heading -->
            <div class="sidebar-heading">
                Book Management
            </div>

            <!-- Book management items moved directly to sidebar -->
            <li class="nav-item <?php echo $currentPage == 'book_list.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="book_list.php">
                    <i class="fas fa-book icon-book"></i>
                    <span>Books Catalog</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage == 'writers_list.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="writers_list.php">
                    <i class="fas fa-pen-nib icon-book"></i>
                    <span>Authors & Writers</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage == 'publisher_list.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="publisher_list.php">
                    <i class="fas fa-building icon-book"></i>
                    <span>Publishers</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage == 'publications_list.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="publications_list.php">
                    <i class="fas fa-newspaper icon-book"></i>
                    <span>Book Publications</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage == 'contributors_list.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="contributors_list.php">
                    <i class="fas fa-users icon-book"></i>
                    <span>Book Contributors</span>
                </a>
            </li>

            <!-- Borrowing Operations Section - Not for encoders -->
            <?php if($_SESSION['role'] !== 'Encoder'): ?>
            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Borrowing Operations
            </div>

            <!-- Borrowing management items -->
            <li class="nav-item <?php echo $currentPage == 'book_borrowing.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="book_borrowing.php">
                    <i class="fas fa-hand-holding-heart icon-borrow"></i>
                    <span>Walk-in Book Borrowing</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage == 'book_reservations.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="book_reservations.php">
                    <i class="fas fa-bookmark icon-borrow"></i>
                    <span>Online Book Reservations</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage == 'borrowed_books.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="borrowed_books.php">
                    <i class="fas fa-book-reader icon-borrow"></i>
                    <span>Issued Books</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage == 'borrowing_history.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="borrowing_history.php">
                    <i class="fas fa-history icon-borrow"></i>
                    <span>Borrowing History</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage == 'fines.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="fines.php">
                    <i class="fas fa-money-bill-wave icon-borrow"></i>
                    <span>Fines Management</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage == 'lost_books.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="lost_books.php">
                    <i class="fas fa-search icon-borrow"></i>
                    <span>Lost Book Records</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage == 'damaged_books.php' ? 'active-page' : ''; ?>">
                <a class="nav-link" href="damaged_books.php">
                    <i class="fas fa-book-medical icon-borrow"></i>
                    <span>Damaged Book Records</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Divider -->
            <hr class="sidebar-divider">

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Removed sidebar toggle top button -->

                    <!-- Topbar Search -->
                    <form
                        class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                                aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Alert Center -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <!-- Counter - Alerts -->
                                <span class="badge badge-danger badge-counter" id="reservationCount">0</span>
                            </a>
                            <!-- Dropdown - Alerts -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header">
                                    Reservation Alerts
                                </h6>
                                <div id="alertsList">
                                    <!-- Alerts will be dynamically inserted here -->
                                </div>
                                <a class="dropdown-item text-center small text-gray-500" href="book_reservations.php">Show All Reservations</a>
                            </div>
                        </li>

                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                                aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small"
                                            placeholder="Search for..." aria-label="Search"
                                            aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>

                        <!-- Nav Item - Messages -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-envelope fa-fw"></i>
                                <!-- Counter - Messages -->
                                <span class="badge badge-danger badge-counter" id="messageCount">0</span>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="messagesDropdown">
                                <h6 class="dropdown-header">
                                    Message Center
                                </h6>
                                <div id="messagesList">
                                    <!-- Messages will be loaded here -->
                                    <div class="text-center p-3">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <a class="dropdown-item text-center small text-gray-500" href="messages.php">Read More Messages</a>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname']; ?></span>
                                <img class="img-profile rounded-circle" src="<?php echo $_SESSION['admin_image']?>" alt="Profile Image">

                                </a>
                                <!-- Dropdown - User Information -->
                                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                    aria-labelledby="userDropdown">
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                        Profile
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                        Logout
                                    </a>
                                </div>
                            </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->



            <!-- End of Footer -->



    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i> 
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="inc/js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/chart.js/Chart.min.js"></script>

    <script src="js/demo/chart-area-demo.js"></script>
    <script src="js/demo/chart-pie-demo.js"></script>
    <script src="js/demo/chart-bar-demo.js"></script>

    <script src="inc/assets/DataTables/datatables.min.js"></script>

    <script>
    // Consolidated JavaScript functions
    $(document).ready(function() {
        // Prevent dropdown menu from closing when clicking on items
        $('.collapse-item').on('click', function(e) {
            e.stopPropagation();
        });

        // Prevent dropdown menu from closing when clicking on items in the Book Management section
        $('#collapseTwo .collapse-item').on('click', function(e) {
        });
        
        // Highlight current page in sidebar
        setActivePage();
        
        // Show page indicator
        showPageIndicator();
    });
    
    // Function to highlight active page in sidebar
    function setActivePage() {
        const currentPage = '<?php echo $currentPage; ?>';
        const pageTitle = '<?php echo $pageTitle; ?>';
        
        // Remove active class from all nav items first
        $('.nav-item').removeClass('active-page');
        
        // Find the link with href matching current page and add active class to its parent
        $(`.nav-link[href="${currentPage}"]`).closest('.nav-item').addClass('active-page');
        
        // Set document title to include the current page
        document.title = pageTitle + " | NBS College Library";
        
        // Update page indicator
        $('#pageIndicator').text('You are in: ' + pageTitle);
    }
    
    // Function to update message count
    function updateMessageCount() {
        fetch('ajax/get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('messageCount');
                if (data.count > 0) {
                    badge.style.display = 'inline';
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(error => console.error('Error updating message count:', error));
    }
    
    // Function to update message preview
    function updateMessages() {
        fetch('ajax/get_latest_messages.php')
            .then(response => response.json())
            .then(data => {
                const messagesList = document.getElementById('messagesList');
                if (!data.messages || data.messages.length === 0) {
                    messagesList.innerHTML = `
                        <div class="text-center p-3">
                            <p class="small text-gray-500">No new messages</p>
                        </div>`;
                    return;
                }

                messagesList.innerHTML = '';
                data.messages.forEach(msg => {
                    messagesList.innerHTML += `
                        <a class="dropdown-item d-flex align-items-center" href="messages.php?user=${msg.sender_id}&role=${msg.sender_role}">
                            <div class="dropdown-list-image mr-3">
                                <img class="rounded-circle" src="${msg.sender_image}" alt="${msg.sender_name}"
                                     style="width: 40px; height: 40px; object-fit: cover;">
                                <div class="status-indicator ${msg.is_read ? 'bg-success' : 'bg-warning'}"></div>
                            </div>
                            <div class="font-weight-bold flex-grow-1">
                                <div class="text-truncate">${msg.message}</div>
                                <div class="small text-gray-500">
                                    ${msg.sender_name} · ${msg.timestamp}
                                </div>
                            </div>
                            ${!msg.is_read ? '<div class="ml-2"><span class="badge badge-danger">New</span></div>' : ''}
                        </a>`;
                });
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                document.getElementById('messagesList').innerHTML = `
                    <div class="text-center p-3">
                        <p class="small text-gray-500">Error loading messages</p>
                    </div>`;
            });
    }
    
    // Function to update reservation alerts
    function updateReservationAlerts() {
        fetch('ajax/get_reservation_alerts.php')
            .then(response => response.json())
            .then(data => {
                const alertsList = document.getElementById('alertsList');
                const reservationCount = document.getElementById('reservationCount');

                if (data.reservations && data.reservations.length > 0) {
                    reservationCount.textContent = data.reservations.length;
                    reservationCount.style.display = 'inline';

                    alertsList.innerHTML = '';
                    data.reservations.forEach(reservation => {
                        alertsList.innerHTML += `
                            <a class="dropdown-item d-flex align-items-center" href="book_reservations.php">
                                <div class="mr-3">
                                    <div class="icon-circle bg-primary">
                                        <i class="fas fa-file-alt text-white"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="small text-gray-500">${reservation.reservation_date}</div>
                                    <span class="font-weight-bold">${reservation.user_name} reserved ${reservation.book_title}</span>
                                </div>
                            </a>
                        `;
                    });
                } else {
                    reservationCount.style.display = 'none';
                    alertsList.innerHTML = '<a class="dropdown-item text-center small text-gray-500">No new reservations</a>';
                }
            })
            .catch(error => {
                console.error('Error loading reservation alerts:', error);
                document.getElementById('alertsList').innerHTML = `
                    <div class="text-center p-3">
                        <p class="small text-gray-500">Error loading reservations</p>
                    </div>`;
            });
    }

    // Initialize all update functions
    updateMessageCount();
    updateMessages();
    updateReservationAlerts();
    
    // Set intervals for periodic updates
    setInterval(updateMessageCount, 30000);
    setInterval(updateMessages, 30000);
    setInterval(updateReservationAlerts, 30000);
    </script>
</body>
</html>
