<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>NBS College Library</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="inc/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="inc/assets/DataTables/datatables.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <!-- <img src="img/nbs-icon.png" alt="Library Logo" width="30" height="50"> -->
                </div>
                <div class="sidebar-brand-text mx-3">NBSC Library <sup></sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                User Operation
            </div>

            <!-- Nav Item - Search Book -->
            <li class="nav-item">
                <a class="nav-link" href="searchbook.php">
                    <i class="fas fa-fw fa-search"></i>
                    <span>Search Book</span>
                </a>
            </li>

            <!-- Nav Item - Cart -->
            <li class="nav-item">
                <a class="nav-link" href="cart.php">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>Cart</span>
                </a>
            </li>

            <!-- Nav Item - Book Reservation -->
            <li class="nav-item">
                <a class="nav-link" href="book_reservations.php">
                    <i class="fas fa-fw fa-bookmark"></i>
                    <span>Book Reservation</span>
                </a>
            </li>

            <!-- Nav Item - Book Borrowing -->
            <li class="nav-item">
                <a class="nav-link" href="book_borrowing.php">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Book Borrowing</span>
                </a>
            </li>

            <!-- Nav Item - Histories -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseHistories"
                    aria-expanded="true" aria-controls="collapseHistories">
                    <i class="fas fa-fw fa-history"></i>
                    <span>Histories</span>
                </a>
                <div id="collapseHistories" class="collapse" aria-labelledby="headingHistories"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">History Types:</h6>
                        <a class="collapse-item" href="borrowing_history.php">Borrowing History</a>
                        <a class="collapse-item" href="reservation_history.php">Reservation History</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

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

                        <!-- Nav Item - Alerts -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <?php
                            // Get ready reservations
                            $ready_query = "SELECT r.id, b.title, r.reserve_date 
                                          FROM reservations r 
                                          JOIN books b ON r.book_id = b.id 
                                          WHERE r.user_id = ? AND r.status = 'Ready'
                                          ORDER BY r.reserve_date DESC";
                            $ready_stmt = $conn->prepare($ready_query);
                            $ready_stmt->bind_param('i', $_SESSION['user_id']);
                            $ready_stmt->execute();
                            $ready_result = $ready_stmt->get_result();
                            $ready_count = $ready_result->num_rows;
                            ?>
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <!-- Counter - Alerts -->
                                <?php if ($ready_count > 0): ?>
                                    <span class="badge badge-danger badge-counter"><?php echo $ready_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <!-- Dropdown - Alerts -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header">
                                    Ready Books Center
                                </h6>
                                <?php if ($ready_count > 0): ?>
                                    <?php while ($ready = $ready_result->fetch_assoc()): ?>
                                        <a class="dropdown-item d-flex align-items-center" href="book_reservations.php">
                                            <div class="mr-3">
                                                <div class="icon-circle bg-success">
                                                    <i class="fas fa-book text-white"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="small text-gray-500"><?php echo date('F d, Y', strtotime($ready['reserve_date'])); ?></div>
                                                <span class="font-weight-bold"><?php echo htmlspecialchars($ready['title']); ?> is ready for pickup!</span>
                                            </div>
                                        </a>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <a class="dropdown-item text-center small text-gray-500" href="#">No books ready for pickup</a>
                                <?php endif; ?>
                                <a class="dropdown-item text-center small text-gray-500" href="book_reservations.php">Show All Reservations</a>
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
                                    <!-- Messages will be dynamically loaded here -->
                                </div>
                                <a class="dropdown-item text-center small text-gray-500" href="messages.php">Read More Messages</a>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php 
                                        if(isset($_SESSION['firstname']) && isset($_SESSION ['lastname'])) {
                                            echo $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
                                        } else {
                                            echo 'User';
                                        }
                                    ?>
                                </span>
                                <img class="img-profile rounded-circle"
                                    src="<?php echo isset($_SESSION['user_image']) && !empty($_SESSION['user_image']) ? $_SESSION['user_image'] : 'img/undraw_profile_1.svg'; ?>"
                                    alt="">
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
    function updateMessages() {
        // Update unread count
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
            });

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
                    const time = new Date(msg.timestamp);
                    const timeAgo = Math.floor((new Date() - time) / 60000); // minutes
                    const timeStr = timeAgo < 60 
                        ? timeAgo + 'm ago' 
                        : Math.floor(timeAgo/60) + 'h ago';
                    
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
                                    ${msg.sender_name} · ${timeStr}
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
</body>
</html>
