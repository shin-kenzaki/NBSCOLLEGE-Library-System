/**
 * Form autosave functionality with tab-specific clearing
 */
document.addEventListener("DOMContentLoaded", function() {
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
        
        localStorage.setItem(storageKey, JSON.stringify(formData));
    }

    // Function to clear a specific tab's data without confirmation
    function clearTabData(tabId) {
        const tabPane = document.querySelector(`#${tabId}`);
        if (!tabPane) return;

        // Clear inputs within the tab
        tabPane.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else if (input.type === 'select-multiple') {
                input.selectedIndex = -1;
                // Clear associated preview if exists
                const previewId = input.id + 'Preview';
                const preview = document.getElementById(previewId);
                if (preview) preview.innerHTML = '';
            } else if (input.type === 'file') {
                input.value = '';
                // Reset associated label
                const label = input.nextElementSibling;
                if (label && label.classList.contains('custom-file-label')) {
                    label.textContent = 'Choose file';
                }
            } else {
                input.value = '';
            }
        });

        // Remove completed status from tab
        const tabButton = document.querySelector(`[href="#${tabId}"]`);
        if (tabButton) tabButton.classList.remove('completed');

        // Save the updated form state
        saveFormData();
    }

    // Bind clear tab buttons
    document.querySelectorAll('.clear-tab-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            // Remove the confirmation here since it's likely handled in form-clear.js
            const tabId = e.currentTarget.dataset.tabId;
            clearTabData(tabId);
        });
    });
    
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
        
        // Re-generate dynamic content if needed
        if (typeof updateISBNFields === 'function') {
            updateISBNFields();
        }
    }
    
    // Save form data periodically
    const autoSaveInterval = setInterval(saveFormData, 1000);
    
    // Save on input changes
    form.addEventListener('input', saveFormData);
    
    // Save on tab changes
    document.querySelectorAll('#formTabs .nav-link').forEach(tab => {
        tab.addEventListener('shown.bs.tab', saveFormData);
    });
    
    // Restore form data on page load
    restoreFormData();

    // Helper function for updating multi-select previews
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
