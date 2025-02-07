document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll('.nav-link');
    tabs.forEach(tab => {
        tab.addEventListener('click', function (event) {
            // event.preventDefault(); // Remove this if you're navigating between pages
            const targetTab = document.querySelector(tab.getAttribute('href'));
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const tabContents = document.querySelectorAll('.tab-pane');
            tabContents.forEach(content => content.classList.remove('show', 'active'));
            targetTab.classList.add('show', 'active');
        });
    });
});
