<!-- Footer -->
<footer class="sticky-footer bg-white">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>Copyright © NBS College 2024</span>
        </div>
    </div>
</footer>
<!-- End of Footer -->

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
}
</style>

<script>
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