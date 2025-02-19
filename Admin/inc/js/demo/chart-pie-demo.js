// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

// Get book stats from PHP
const bookStats = {
    borrowed: parseInt(document.getElementById('borrowed').value),
    overdue: parseInt(document.getElementById('overdue').value),
    returned: parseInt(document.getElementById('returned').value)
};

// Pie Chart Example
var ctx = document.getElementById("myPieChart");
var myPieChart = new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: ["Borrowed", "Overdue", "Returned"],
    datasets: [{
      data: [bookStats.borrowed, bookStats.overdue, bookStats.returned],
      backgroundColor: ['#4e73df', '#e74a3b', '#1cc88a'],
      hoverBackgroundColor: ['#2e59d9', '#be2617', '#17a673'],
      hoverBorderColor: "rgba(234, 236, 244, 1)",
    }],
  },
  options: {
    maintainAspectRatio: false,
    tooltips: {
      backgroundColor: "rgb(255,255,255)",
      bodyFontColor: "#858796",
      borderColor: '#dddfeb',
      borderWidth: 1,
      xPadding: 15,
      yPadding: 15,
      displayColors: false,
      caretPadding: 10,
    },
    legend: {
      display: true
    },
    cutoutPercentage: 80,
  },
});
