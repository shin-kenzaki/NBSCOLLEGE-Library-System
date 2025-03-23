/**
 * Form navigation and tab handling
 */
document.addEventListener("DOMContentLoaded", function() {
    // Variables to track form completion
    const totalTabs = document.querySelectorAll('#formTabs .nav-link').length;
    let completedTabs = 0;
    let currentTabIndex = 0;
    
    const tabs = document.querySelectorAll('#formTabs .nav-link');
    const tabContents = document.querySelectorAll('.tab-pane');
    const progressBar = document.getElementById('formProgressBar');
    
    // Function to update progress bar
    function updateProgressBar() {
        const progressPercentage = (currentTabIndex / (totalTabs - 1)) * 100;
        progressBar.style.width = progressPercentage + '%';
        progressBar.setAttribute('aria-valuenow', progressPercentage);
    }
    
    // Function to validate current tab
    function validateCurrentTab() {
        const currentTab = tabs[currentTabIndex];
        const currentTabId = currentTab.getAttribute('href').substring(1);
        const currentTabPane = document.getElementById(currentTabId);
        
        let isValid = true;
        
        // Check required fields in the current tab
        const requiredFields = currentTabPane.querySelectorAll('input[required], select[required], textarea[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // If valid, mark tab as completed
        if (isValid) {
            currentTab.classList.add('completed');
            
            // Count completed tabs
            completedTabs = document.querySelectorAll('#formTabs .nav-link.completed').length;
        }
        
        return isValid;
    }
    
    // Next button click handler
    document.querySelectorAll('.next-tab').forEach(button => {
        button.addEventListener('click', function() {
            if (validateCurrentTab()) {
                const nextTabId = this.getAttribute('data-next');
                const nextTab = document.getElementById(nextTabId);
                
                // Find the index of the next tab
                tabs.forEach((tab, index) => {
                    if (tab.id === nextTabId) {
                        currentTabIndex = index;
                    }
                });
                
                // Update progress bar
                updateProgressBar();
                
                // Activate the tab with Bootstrap
                $(nextTab).tab('show');
            } else {
                alert('Please fill in all required fields before proceeding.');
            }
        });
    });
    
    // Previous button click handler
    document.querySelectorAll('.prev-tab').forEach(button => {
        button.addEventListener('click', function() {
            const prevTabId = this.getAttribute('data-prev');
            const prevTab = document.getElementById(prevTabId);
            
            // Find the index of the previous tab
            tabs.forEach((tab, index) => {
                if (tab.id === prevTabId) {
                    currentTabIndex = index;
                }
            });
            
            // Update progress bar
            updateProgressBar();
            
            // Trigger click on the previous tab
            $(prevTab).tab('show');
        });
    });
    
    // Disable direct tab clicking (completely prevent it)
    tabs.forEach((tab) => {
        tab.addEventListener('click', function(e) {
            // Always prevent the default tab switching behavior
            e.preventDefault();
            e.stopPropagation();
            
            // Only allow clicking on completed tabs
            if (tab.classList.contains('completed')) {
                // If it's a completed tab, allow navigation to it
                currentTabIndex = Array.from(tabs).indexOf(this);
                updateProgressBar();
                
                // Use Bootstrap's tab method to show the tab
                $(this).tab('show');
            } else if (this !== tabs[currentTabIndex]) {
                // If trying to access an uncompleted tab that's not the current one
                alert('Please complete the current section before skipping ahead.');
            }
            return false;
        });
    });
    
    // Add subject entry
    document.getElementById('add-subject').addEventListener('click', function() {
        const subjectEntries = document.getElementById('subject-entries');
        const newEntry = document.createElement('div');
        newEntry.className = 'subject-entry card p-3 mb-3';
        newEntry.innerHTML = `
            <button type="button" class="btn btn-danger btn-sm remove-subject">
                <i class="fas fa-times"></i>
            </button>
            <div class="form-group">
                <label>Subject Category</label>
                <select class="form-control" name="subject_categories[]">
                    <option value="">Select Subject Category</option>
                    ${Array.from(document.querySelector('select[name="subject_categories[]"]').options)
                        .map(opt => `<option value="${opt.value}">${opt.textContent}</option>`)
                        .join('')}
                </select>
            </div>
            <div class="form-group">
                <label>Subject Detail</label>
                <textarea class="form-control" name="subject_paragraphs[]" rows="3"></textarea>
            </div>
        `;
        subjectEntries.appendChild(newEntry);
    });
    
    // Remove subject entry
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-subject')) {
            const subjectEntries = document.getElementById('subject-entries');
            if (subjectEntries.children.length > 1) {
                e.target.closest('.subject-entry').remove();
            } else {
                alert('At least one subject entry is required.');
            }
        }
    });
});
