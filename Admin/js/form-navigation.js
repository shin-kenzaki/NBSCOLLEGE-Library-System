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
    
    // Get navigation buttons
    const prevButton = document.getElementById('prevTabBtn');
    const nextButton = document.getElementById('nextTabBtn');
    
    // Function to update button states
    function updateNavigationButtons() {
        // Disable/enable previous button
        if (currentTabIndex === 0) {
            prevButton.disabled = true;
        } else {
            prevButton.disabled = false;
        }
        
        // Change next button text on last tab
        if (currentTabIndex === totalTabs - 1) {
            nextButton.innerHTML = 'Submit <i class="fas fa-check"></i>';
            nextButton.classList.add('btn-success');
            nextButton.classList.remove('btn-primary');
        } else {
            nextButton.innerHTML = 'Next <i class="fas fa-chevron-right"></i>';
            nextButton.classList.add('btn-primary');
            nextButton.classList.remove('btn-success');
        }
    }
    
    // Function to update progress bar
    function updateProgressBar() {
        const progressPercentage = (currentTabIndex / (totalTabs - 1)) * 100;
        progressBar.style.width = progressPercentage + '%';
        progressBar.setAttribute('aria-valuenow', progressPercentage);
        
        // Update button states
        updateNavigationButtons();
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
    
    // Function to navigate to the next tab
    function goToNextTab() {
        if (validateCurrentTab()) {
            if (currentTabIndex < totalTabs - 1) {
                // Go to next tab
                currentTabIndex++;
                $(tabs[currentTabIndex]).tab('show');
                updateProgressBar();
            } else {
                // We're on the last tab, submit the form
                if (confirm('Submit the book information?')) {
                    document.getElementById('bookForm').submit();
                }
            }
        } else {
            alert('Please fill in all required fields before proceeding.');
        }
    }
    
    // Function to navigate to the previous tab
    function goToPrevTab() {
        if (currentTabIndex > 0) {
            currentTabIndex--;
            $(tabs[currentTabIndex]).tab('show');
            updateProgressBar();
        }
    }
    
    // Add event listeners for the navigation buttons
    nextButton.addEventListener('click', goToNextTab);
    prevButton.addEventListener('click', goToPrevTab);
    
    // Initialize button states
    updateNavigationButtons();
    
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
            // Always prevent the default tab switching behavior first
            e.preventDefault();
            e.stopPropagation();
            
            const clickedTabIndex = Array.from(tabs).indexOf(this);
            
            // Only allow clicking on completed tabs or the current tab
            if (tab.classList.contains('completed')) {
                // If it's a completed tab, allow navigation to it
                currentTabIndex = clickedTabIndex;
                updateProgressBar();
                
                // Use Bootstrap's tab method to show the tab
                $(this).tab('show');
            } else if (this === tabs[currentTabIndex]) {
                // Clicking on current tab - do nothing but allow it
                return false;
            } else {
                // Prevent navigation to any uncompleted tab
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
    
    // Add clear tab functionality
    document.querySelectorAll('.clear-tab-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab-id');
            const tabPane = document.getElementById(tabId);
            
            if (confirm('Are you sure you want to clear all fields in this tab?')) {
                clearTabFields(tabPane);
            }
        });
    });
    
    // Function to clear all fields within a tab
    function clearTabFields(tabPane) {
        // Clear text inputs, textareas, and selects
        tabPane.querySelectorAll('input[type="text"], input[type="number"], input[type="url"], textarea, select:not([multiple])').forEach(field => {
            if (!field.readOnly) {
                field.value = '';
            }
        });
        
        // Clear checkboxes
        tabPane.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Clear file inputs
        tabPane.querySelectorAll('input[type="file"]').forEach(fileInput => {
            fileInput.value = '';
            // Reset custom file label if Bootstrap custom file input is used
            const label = fileInput.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                const originalLabel = label.getAttribute('data-original-text') || 
                                     (fileInput.id === 'front_image' ? 'Front Cover' : 'Back Cover');
                label.textContent = originalLabel;
            }
        });
        
        // Clear multi-select dropdowns
        tabPane.querySelectorAll('select[multiple]').forEach(select => {
            Array.from(select.options).forEach(option => {
                option.selected = false;
            });
            
            // Clear any associated preview divs
            const selectId = select.id;
            const previewId = selectId + 'Preview';
            const previewElement = document.getElementById(previewId);
            if (previewElement) {
                previewElement.innerHTML = '';
            }
        });
        
        // Special handling for subject entries
        if (tabPane.id === 'subject-entry') {
            handleSubjectTabClearing(tabPane);
        }
        
        // Special handling for accession entries
        if (tabPane.id === 'local-info') {
            handleAccessionTabClearing(tabPane);
        }
    }
    
    // Helper function for subject tab clearing
    function handleSubjectTabClearing(tabPane) {
        const subjectEntries = tabPane.querySelector('#subject-entries');
        
        // Keep only the first subject entry and clear its fields
        while (subjectEntries.children.length > 1) {
            subjectEntries.removeChild(subjectEntries.lastChild);
        }
        
        // Clear the remaining entry
        const firstEntry = subjectEntries.querySelector('.subject-entry');
        if (firstEntry) {
            firstEntry.querySelectorAll('select, textarea').forEach(field => {
                field.value = '';
            });
        }
    }
    
    // Helper function for accession tab clearing
    function handleAccessionTabClearing(tabPane) {
        const accessionContainer = tabPane.querySelector('#accessionContainer');
        
        // Keep only the first accession group and clear its fields
        if (accessionContainer) {
            while (accessionContainer.children.length > 1) {
                accessionContainer.removeChild(accessionContainer.lastChild);
            }
            
            // Clear the remaining entry
            const firstGroup = accessionContainer.querySelector('.accession-group');
            if (firstGroup) {
                firstGroup.querySelectorAll('input:not([readonly])').forEach(field => {
                    if (field.type === 'number' && field.name.includes('number_of_copies')) {
                        field.value = '1';
                    } else {
                        field.value = '';
                    }
                });
            }
        }
    }
});
