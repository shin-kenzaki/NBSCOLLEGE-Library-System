/**
 * Form autosave functionality
 */
document.addEventListener("DOMContentLoaded", function() {
    // Form autosave functionality
    const formId = 'bookForm';
    const storageKey = 'bookFormData';
    const form = document.getElementById(formId);
    
    // Function to save form data to localStorage
    function saveFormData() {
        const formData = {};
        
        // Save text inputs, textareas, and selects
        form.querySelectorAll('input:not([type="file"]), textarea, select').forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                formData[input.name + '-' + input.value] = input.checked;
            } else if (input.type === 'select-multiple') {
                formData[input.name] = Array.from(input.selectedOptions).map(option => option.value);
            } else {
                formData[input.name] = input.value;
            }
        });
        
        // Save current active tab
        const activeTab = document.querySelector('#formTabs .nav-link.active');
        if (activeTab) {
            formData['activeTab'] = activeTab.id;
        }
        
        // Save progress bar state
        const progressBar = document.getElementById('formProgressBar');
        formData['progressValue'] = progressBar.style.width;
        
        // Save completed tabs
        const completedTabs = Array.from(document.querySelectorAll('#formTabs .nav-link.completed')).map(tab => tab.id);
        formData['completedTabs'] = completedTabs;
        
        // Save to localStorage
        localStorage.setItem(storageKey, JSON.stringify(formData));
    }
    
    // Function to restore form data from localStorage
    function restoreFormData() {
        const savedData = localStorage.getItem(storageKey);
        if (!savedData) return;
        
        const formData = JSON.parse(savedData);
        
        // Restore text inputs, textareas, and selects
        form.querySelectorAll('input:not([type="file"]), textarea, select').forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                if (formData[input.name + '-' + input.value]) {
                    input.checked = true;
                }
            } else if (input.type === 'select-multiple' && formData[input.name]) {
                const values = formData[input.name];
                Array.from(input.options).forEach(option => {
                    option.selected = values.includes(option.value);
                });
                
                // Update the preview for multi-selects
                if (input.id === 'authorSelect') updatePreview('authorSelect', 'authorPreview');
                if (input.id === 'coAuthorsSelect') updatePreview('coAuthorsSelect', 'coAuthorsPreview');
                if (input.id === 'editorsSelect') updatePreview('editorsSelect', 'editorsPreview');
            } else if (formData[input.name] !== undefined) {
                input.value = formData[input.name];
            }
        });
        
        // Restore custom file input labels
        document.querySelectorAll('.custom-file-input').forEach(input => {
            const label = input.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                // Only update if we have a filename saved
                if (formData[input.name + '-label']) {
                    label.textContent = formData[input.name + '-label'];
                }
            }
        });
        
        // Restore active tab
        if (formData['activeTab']) {
            const tabToActivate = document.getElementById(formData['activeTab']);
            if (tabToActivate) {
                $(tabToActivate).tab('show');
            }
        }
        
        // Restore progress bar
        if (formData['progressValue']) {
            const progressBar = document.getElementById('formProgressBar');
            progressBar.style.width = formData['progressValue'];
            progressBar.setAttribute('aria-valuenow', parseInt(formData['progressValue']));
        }
        
        // Restore completed tabs
        if (formData['completedTabs']) {
            formData['completedTabs'].forEach(tabId => {
                const tab = document.getElementById(tabId);
                if (tab) tab.classList.add('completed');
            });
        }
        
        // Re-generate dynamic content based on restored data
        if (typeof updateISBNFields === 'function') {
            updateISBNFields();
        }
    }
    
    // Save form data periodically (every 1 second)
    const autoSaveInterval = setInterval(saveFormData, 1000);
    
    // Save on input changes
    form.addEventListener('input', function() {
        saveFormData();
    });
    
    // Save on tab changes
    document.querySelectorAll('#formTabs .nav-link').forEach(tab => {
        tab.addEventListener('shown.bs.tab', saveFormData);
    });
    
    // Clear form data on successful submission
    form.addEventListener('submit', function() {
        localStorage.removeItem(storageKey);
        clearInterval(autoSaveInterval);
    });
    
    // Restore form data on page load
    restoreFormData();
    
    // Add a "Clear Form" button
    const formActions = document.querySelector('.container-fluid.d-flex.justify-content-between.align-items-center.mb-4');
    if (formActions) {
        const clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'btn btn-warning mr-2';
        clearButton.innerHTML = '<i class="fas fa-trash"></i> Clear Form';
        clearButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all form data?')) {
                localStorage.removeItem(storageKey);
                location.reload();
            }
        });
        
        // Insert before the Save button
        formActions.insertBefore(clearButton, formActions.querySelector('.btn.btn-success'));
    }

    // Helper function needed for restoreFormData
    function updatePreview(selectId, previewId) {
        const select = document.getElementById(selectId);
        const preview = document.getElementById(previewId);
        if (!select || !preview) return;
        
        const selectedOptions = Array.from(select.selectedOptions).map(option => {
            return `<span class="badge bg-secondary mr-1 text-white">${option.text} <i class="fas fa-times remove-icon" data-value="${option.value}"></i></span>`;
        });
        preview.innerHTML = selectedOptions.join(' ');
    }
});
