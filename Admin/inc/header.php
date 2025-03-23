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

</head>

<body id="page-top">

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
            <li class="nav-item active">
                <a class="nav-link" href="<?php 
                    $base_url = '/Library-System/Admin/';
                    if ($_SESSION['role'] === 'Admin') {
                        echo $base_url . 'dashboard.php';
                    } elseif ($_SESSION['role'] === 'Librarian' || $_SESSION['role'] === 'Assistant') {
                        echo $base_url . 'dashboard.php';
                    } elseif ($_SESSION['role'] === 'Encoder') {
                        echo $base_url . 'dashboard.php';
                    }
                ?>">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Admin Operation
            </div>

            <?php if($_SESSION['role'] === 'Admin'): ?>
            <!-- Full management menu for admin -->
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUsers"
                    aria-expanded="true" aria-controls="collapseUsers">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </a>
                <div id="collapseUsers" class="collapse" aria-labelledby="headingUsers" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Manage Users:</h6>
                        <a class="collapse-item" href="admins_list.php">Admin Users</a>
                        <a class="collapse-item" href="users_list.php">Users</a>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Book Operation
            </div>

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Book Management</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Book Module:</h6>
                        <a class="collapse-item" href="book_list.php" data-toggle="dropdown">Book List</a>
                        <a class="collapse-item" href="writers_list.php" data-toggle="dropdown">Writers List</a>
                        <a class="collapse-item" href="publisher_list.php" data-toggle="dropdown">Publisher List</a>
                        <a class="collapse-item" href="publications_list.php" data-toggle="dropdown">Publications List</a>
                        <a class="collapse-item" href="contributors_list.php" data-toggle="dropdown">Contributors List</a>
                    </div>
                </div>
            </li>

            <?php elseif($_SESSION['role'] === 'Librarian' || $_SESSION['role'] === 'Assistant'): ?>
            <!-- Limited menu for librarian and assistant -->
            
            <!-- Add User Management for Librarian and Assistant but without admin access -->
            <li class="nav-item">
                <a class="nav-link" href="users_list.php">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Book Operation
            </div>

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Book Management</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Book Module:</h6>
                        <a class="collapse-item" href="add-book.php">Add Book</a>
                        <a class="collapse-item" href="book_list.php" data-toggle="dropdown">Book List</a>
                        <a class="collapse-item" href="writers_list.php" data-toggle="dropdown">Writers List</a>
                        <a class="collapse-item" href="publisher_list.php" data-toggle="dropdown">Publisher List</a>
                        <a class="collapse-item" href="publications_list.php" data-toggle="dropdown">Publications List</a>
                        <a class="collapse-item" href="contributors_list.php" data-toggle="dropdown">Contributors List</a>
                    </div>
                </div>
            </li>
            <?php elseif($_SESSION['role'] === 'Encoder'): ?>
            <!-- Limited menu for encoder -->

            <!-- Heading -->
            <div class="sidebar-heading">
                Book Operation
            </div>

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Book Management</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Book Module:</h6>
                        <a class="collapse-item" href="add-book.php">Add Book</a>
                        <a class="collapse-item" href="book_list.php" data-toggle="dropdown">Book List</a>
                        <a class="collapse-item" href="writers_list.php" data-toggle="dropdown">Writers List</a>
                        <a class="collapse-item" href="publisher_list.php" data-toggle="dropdown">Publisher List</a>
                        <a class="collapse-item" href="publications_list.php" data-toggle="dropdown">Publications List</a>
                        <a class="collapse-item" href="contributors_list.php" data-toggle="dropdown">Contributors List</a>
                    </div>
                </div>
            </li>
            <?php endif; ?>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Borrowing Operation
            </div>

            <!-- Nav Item - Books Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBooks"
                    aria-expanded="true" aria-controls="collapseBooks">
                    <i class="fas fa-book"></i>
                    <span>Borrowing Management</span>
                </a>
                <div id="collapseBooks" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Borrowing Management:</h6>
                        <a class="collapse-item" href="book_borrowing.php">Book Borrowing</a>
                        <a class="collapse-item" href="book_reservations.php">Book Reservations</a>
                        <a class="collapse-item" href="borrowed_books.php">Borrowed Books</a>
                        <a class="collapse-item" href="borrowing_history.php">Borrowing History</a>
                        <a class="collapse-item" href="fines.php">Manage Fines</a>
                        <a class="collapse-item" href="lost_books.php">Lost Book Records</a>
                        <a class="collapse-item" href="damaged_books.php">Damaged Book Records</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

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
    $(document).ready(function() {
        $('.collapse-item').on('click', function(e) {
            e.stopPropagation();
        });

        // Prevent dropdown menu from closing when clicking on items in the Book Management section
        $('#collapseTwo .collapse-item').on('click', function(e) {
        });
        
        // Load sidebar state from localStorage when page loads
        $(document).ready(function() {
            // Check if sidebar state is saved in localStorage
            const sidebarState = localStorage.getItem('sidebarToggled');
            
            // If the sidebar was toggled (minimized) previously
            if (sidebarState === 'true') {
                $('body').addClass('sidebar-toggled');
                $('.sidebar').addClass('toggled');
            }
        });
        
        // Save sidebar state when toggle buttons are clicked
        $('#sidebarToggle, #sidebarToggleTop').on('click', function() {
            // If sidebar has toggled class after click, it's minimized
            setTimeout(function() {
                const isMinimized = $('.sidebar').hasClass('toggled');
                localStorage.setItem('sidebarToggled', isMinimized);
            }, 50); // Small delay to ensure classes are updated
        });
    });
    </script>

    <script>
    // Update unread message count
    function updateMessageCount() {
        fetch('ajax/get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('messageCount').textContent = data.count;
            });
    }

    // Update count every 30 seconds
    setInterval(updateMessageCount, 30000);
    updateMessageCount();
    </script>

    <script>
    // Update unread message count and message preview
    function updateMessages() {
        // Update unread count
        fetch('ajax/get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('messageCount').textContent = data.count || '';
            });

        // Update message preview
        fetch('ajax/get_latest_messages.php')
            .then(response => response.json())
            .then(data => {
                const messagesList = document.getElementById('messagesList');
                messagesList.innerHTML = '';
                
                data.messages.slice(0, 4).forEach(msg => {
                    const time = new Date(msg.timestamp);
                    const timeAgo = Math.floor((new Date() - time) / 60000); // minutes
                    
                    messagesList.innerHTML += `
                        <a class="dropdown-item d-flex align-items-center" href="messages.php?user=${msg.sender_id}">
                            <div class="dropdown-list-image mr-3">
                                <img class="rounded-circle" src="${msg.sender_image || 'img/undraw_profile.svg'}" alt="...">
                                <div class="status-indicator ${msg.is_read ? 'bg-success' : 'bg-warning'}"></div>
                            </div>
                            <div class="font-weight-bold">
                                <div class="text-truncate">${msg.message}</div>
                                <div class="small text-gray-500">${msg.sender_name} · ${timeAgo}m</div>
                            </div>
                        </a>
                    `;
                });
            });
    }

    // Update every 30 seconds
    setInterval(updateMessages, 30000);
    updateMessages();
    </script>

    <script>
    // Add this to your existing scripts
    function updateUnreadCount() {
        fetch('ajax/get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('unreadMessageCount');
                badge.textContent = data.count > 0 ? data.count : '';
                badge.style.display = data.count > 0 ? 'block' : 'none';
            });
    }

    // Update count every 30 seconds
    setInterval(updateUnreadCount, 30000);
    updateUnreadCount();
    </script>

    <script>
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

    // Update count every 30 seconds
    setInterval(updateMessageCount, 30000);
    // Initial update
    updateMessageCount();
    </script>

    <script>
    function updateMessages() {
        // Update message preview
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

    // Update messages every 30 seconds
    setInterval(updateMessages, 30000);
    // Initial load
    updateMessages();
    </script>

    <script>
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

    // Update reservation alerts every 30 seconds
    setInterval(updateReservationAlerts, 30000);
    // Initial load
    updateReservationAlerts();
    </script>

    <script>
    function updateMessagesAndCounts() {
        // Update message count
        fetch('ajax/get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const messageCountBadge = document.getElementById('messageCount');
                messageCountBadge.textContent = data.count > 99 ? '99+' : data.count;
                messageCountBadge.style.display = data.count > 0 ? 'inline' : 'none';
            })
            .catch(error => console.error('Error updating message count:', error));

        // Update messages
        fetch('ajax/get_latest_messages.php')
            .then(response => response.json())
            .then(data => {
                const messagesList = document.getElementById('messagesList');
                messagesList.innerHTML = ''; // Clear existing messages

                if (!data.messages || data.messages.length === 0) {
                    messagesList.innerHTML = '<p class="text-center text-gray-500">No new messages</p>';
                    return;
                }

                data.messages.forEach(msg => {
                    const messageLink = `messages.php?user=${msg.sender_id}&role=${msg.sender_role}`;
                    const messageItem = document.createElement('a');
                    messageItem.classList.add('dropdown-item', 'd-flex', 'align-items-center');
                    messageItem.href = messageLink;

                    messageItem.innerHTML = `
                        <div class="dropdown-list-image mr-3">
                            <img class="rounded-circle" src="${msg.sender_image}" alt="${msg.sender_name}" style="width: 40px; height: 40px; object-fit: cover;">
                            <div class="status-indicator ${msg.is_read ? 'bg-success' : 'bg-warning'}"></div>
                        </div>
                        <div class="font-weight-bold flex-grow-1">
                            <div class="text-truncate">${msg.message}</div>
                            <div class="small text-gray-500">${msg.sender_name} · ${msg.timestamp}</div>
                        </div>
                        ${!msg.is_read ? '<div class="ml-2"><span class="badge badge-danger">New</span></div>' : ''}
                    `;
                    messagesList.appendChild(messageItem);
                });
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                messagesList.innerHTML = '<p class="text-center text-gray-500">Error loading messages</p>';
            });
    }

    // Update messages and counts every 30 seconds
    setInterval(updateMessagesAndCounts, 30000);
    // Initial load
    updateMessagesAndCounts();
    </script>
</body>
</html>
