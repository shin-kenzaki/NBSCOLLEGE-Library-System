<!-- Footer -->
<footer class="sticky-footer bg-white">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>Copyright © NBS College 2024</span>
        </div>
    </div>
</footer>
<!-- End of Footer -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<style>
/* Ensure the content wrapper takes up minimum full height of viewport */
#content-wrapper {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Push the footer to the bottom */
#content {
    flex: 1 0 auto;
}

/* Make the footer stick to the bottom */
.sticky-footer {
    flex-shrink: 0;
    padding: 1rem 0;
    margin-top: auto;
    width: 100%;
    margin-top: 30px; /* Increase top margin for better separation */
}

/* Ensure proper spacing in the footer */
.copyright {
    padding: 0.5rem 0;
    font-size: 0.875rem;
}

/* Fix for page content to prevent overlapping with footer */
body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.content-container {
    flex: 1 0 auto;
}

/* Fix scroll to top button positioning */
.scroll-to-top {
    position: fixed;
    right: 1rem;
    bottom: 1rem;
    width: 2.75rem;
    height: 2.75rem;
    color: white;
    background: rgba(90, 92, 105, 0.5);
    z-index: 1055; /* Increased z-index */
    border-radius: 0.35rem !important;
    transition: background-color 0.3s ease;
    /* Added Flexbox for centering */
    display: flex;
    align-items: center;
    justify-content: center;
}

.scroll-to-top:hover {
    background-color: #5a5c69;
    text-decoration: none;
}

.scroll-to-top i {
    font-weight: 800;
}
</style>

<script>
// Initialize scroll to top button functionality
document.addEventListener('DOMContentLoaded', function() {
    const scrollToTop = document.querySelector('.scroll-to-top');
    
    // Display button only when user scrolls down
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 100) {
            scrollToTop.style.display = "block";
        } else {
            scrollToTop.style.display = "none";
        }
    });
    
    // Hide the button initially when page loads
    scrollToTop.style.display = "none";
    
    // Smooth scroll to top
    scrollToTop.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({top: 0, behavior: 'smooth'});
    });
});

function updateReservationAlerts() {
    fetch('get_pending_reservations.php')
        .then(response => response.json())
        .then(data => {
            const alertsList = document.getElementById('alertsList');
            const reservationCount = document.getElementById('reservationCount');
            
            // Update the counter
            reservationCount.textContent = data.total_count > 0 ? data.total_count : '';
            
            // Clear existing alerts
            alertsList.innerHTML = '';
            
            // Add new alerts
            data.reservations.forEach(reservation => {
                alertsList.innerHTML += `
                    <a class="dropdown-item d-flex align-items-center" href="book_reservations.php">
                        <div class="mr-3">
                            <div class="icon-circle bg-primary">
                                <i class="fas fa-book text-white"></i>
                            </div>
                        </div>
                        <div>
                            <div class="small text-gray-500">${reservation.time_ago}</div>
                            <span class="font-weight-bold">${reservation.user_name}</span>
                            reserved "${reservation.book_title}"
                        </div>
                    </a>
                `;
            });
        })
        .catch(error => console.error('Error fetching reservations:', error));
}

// Update alerts initially and every 30 seconds
updateReservationAlerts();
setInterval(updateReservationAlerts, 30000);
</script>
</body>
</html>