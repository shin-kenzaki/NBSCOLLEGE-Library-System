document.addEventListener('DOMContentLoaded', function() {
    // Clear individual tab sections
    document.querySelectorAll('.clear-tab-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab-id');
            if (confirm('Are you sure you want to clear all fields in this tab?')) {
                clearTab(tabId);
            }
        });
    });

    // Clear entire form
    document.querySelector('[data-clear-form]').addEventListener('click', function() {
        if (confirm('Are you sure you want to clear the entire form?')) {
            clearAllTabs();
        }
    });

    function clearTab(tabId) {
        const tab = document.getElementById(tabId);
        if (!tab) return;

        // Clear all inputs within the tab
        tab.querySelectorAll('input:not([readonly]), textarea').forEach(input => {
            input.value = '';
        });

        // Reset dropdowns with special handling
        const specialDropdowns = ['content_type', 'media_type', 'carrier_type', 'language', 'status'];
        tab.querySelectorAll('select').forEach(select => {
            if (specialDropdowns.includes(select.id)) {
                // Reset to first option for special dropdowns
                select.selectedIndex = 0;
            } else {
                // Clear other dropdowns
                select.value = '';
            }
        });

        // Clear checkboxes
        tab.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Reset file inputs
        tab.querySelectorAll('input[type="file"]').forEach(fileInput => {
            fileInput.value = '';
            // Reset the file input label
            const label = fileInput.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = label.getAttribute('data-default-text') || 'Choose file';
            }
        });

        // Preserve system information fields
        const preserveFields = ['entered_by', 'date_added', 'last_update'];
        preserveFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = field.getAttribute('value');
            }
        });
    }

    function clearAllTabs() {
        const tabs = ['title-proper', 'subject-entry', 'abstracts', 'description', 'local-info', 'publication'];
        tabs.forEach(tabId => clearTab(tabId));

        // Reset progress bar
        const progressBar = document.getElementById('formProgressBar');
        if (progressBar) {
            progressBar.style.width = '0%';
            progressBar.setAttribute('aria-valuenow', 0);
        }

        // Reset to first tab
        const firstTab = document.querySelector('#formTabs .nav-link');
        if (firstTab) {
            $(firstTab).tab('show');
        }

        // Remove 'completed' class from all tabs
        document.querySelectorAll('#formTabs .nav-link').forEach(tab => {
            tab.classList.remove('completed');
        });

        // Reset current tab index
        window.currentTabIndex = 0;
    }
});