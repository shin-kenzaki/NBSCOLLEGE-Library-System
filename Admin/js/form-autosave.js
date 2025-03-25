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
        
        // Save accession groups data with more details
        const accessionGroups = [];
        document.querySelectorAll('.accession-group').forEach(group => {
            const accessionInput = group.querySelector('.accession-input');
            const copiesInput = group.querySelector('.copies-input');
            const isbnInput = group.querySelector('input[name^="isbn"]');
            const seriesInput = group.querySelector('input[name^="series"]');
            const volumeInput = group.querySelector('input[name^="volume"]');
            const editionInput = group.querySelector('input[name^="edition"]');
            
            if (accessionInput && copiesInput) {
                accessionGroups.push({
                    accession: accessionInput.value,
                    copies: copiesInput.value,
                    isbn: isbnInput ? isbnInput.value : '',
                    series: seriesInput ? seriesInput.value : '',
                    volume: volumeInput ? volumeInput.value : '',
                    edition: editionInput ? editionInput.value : ''
                });
            }
        });
        formData['accessionGroups'] = accessionGroups;

        // Save call numbers and shelf locations
        const callNumberData = [];
        const callNumberContainers = document.querySelectorAll('#callNumberContainer .input-group');
        callNumberContainers.forEach(container => {
            const callNumberInput = container.querySelector('.call-number-input');
            const shelfLocationSelect = container.querySelector('.shelf-location-select');
            const copyNumberInput = container.querySelector('.copy-number-input');
            const accessionLabel = container.querySelector('.input-group-text');
            
            if (callNumberInput && shelfLocationSelect) {
                callNumberData.push({
                    callNumber: callNumberInput.value,
                    shelfLocation: shelfLocationSelect.value,
                    copyNumber: copyNumberInput ? copyNumberInput.value : '',
                    accessionLabel: accessionLabel ? accessionLabel.textContent : ''
                });
            }
        });
        formData['callNumberData'] = callNumberData;
        
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

        // If this is the form-wide clear
        if (tabId === 'all') {
            // Clear progress data
            localStorage.removeItem('formProgress');
            localStorage.removeItem('completedTabs');
            
            // Reset UI progress
            const progressBar = document.getElementById('formProgressBar');
            if (progressBar) {
                progressBar.style.width = '0%';
                progressBar.setAttribute('aria-valuenow', 0);
            }

            // Remove completed status from all tabs
            document.querySelectorAll('#formTabs .nav-link').forEach(tab => {
                tab.classList.remove('completed');
            });

            // Clear accession groups
            const accessionContainer = document.getElementById('accessionContainer');
            if (accessionContainer) {
                const firstGroup = accessionContainer.querySelector('.accession-group');
                if (firstGroup) {
                    accessionContainer.innerHTML = '';
                    accessionContainer.appendChild(firstGroup);
                }
            }

            // Clear call numbers
            const callNumberContainer = document.getElementById('callNumberContainer');
            if (callNumberContainer) {
                callNumberContainer.innerHTML = '';
            }
        }

        // Save the updated form state
        saveFormData();
    }

    // Bind clear tab buttons
    document.querySelectorAll('.clear-tab-btn').forEach(button => {
        button.addEventListener('click', (e) => {
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

        // Restore accession groups with details
        if (formData['accessionGroups']) {
            const accessionContainer = document.getElementById('accessionContainer');
            if (accessionContainer) {
                accessionContainer.innerHTML = ''; // Clear existing groups
                formData['accessionGroups'].forEach((group, index) => {
                    const groupElement = createAccessionGroup(index + 1);
                    groupElement.querySelector('.accession-input').value = group.accession;
                    groupElement.querySelector('.copies-input').value = group.copies;
                    accessionContainer.appendChild(groupElement);
                });
                
                // After creating all groups, update ISBN fields
                if (typeof updateISBNFields === 'function') {
                    updateISBNFields();
                    
                    // Then restore the saved values for the detail fields
                    setTimeout(() => {
                        const groups = document.querySelectorAll('.accession-group');
                        formData['accessionGroups'].forEach((groupData, index) => {
                            if (index < groups.length) {
                                const group = groups[index];
                                const isbnInput = group.querySelector('input[name^="isbn"]');
                                const seriesInput = group.querySelector('input[name^="series"]');
                                const volumeInput = group.querySelector('input[name^="volume"]');
                                const editionInput = group.querySelector('input[name^="edition"]');
                                
                                if (isbnInput) isbnInput.value = groupData.isbn || '';
                                if (seriesInput) seriesInput.value = groupData.series || '';
                                if (volumeInput) volumeInput.value = groupData.volume || '';
                                if (editionInput) editionInput.value = groupData.edition || '';
                            }
                        });
                    }, 100);
                }
            }
        }

        // Restore call numbers and shelf locations
        if (formData['callNumberData'] && formData['callNumberData'].length > 0) {
            const callNumberContainer = document.getElementById('callNumberContainer');
            if (callNumberContainer && callNumberContainer.children.length === 0) {
                // Only restore if call number fields haven't been generated yet
                formData['callNumberData'].forEach(data => {
                    const callNumberDiv = document.createElement('div');
                    callNumberDiv.className = 'input-group mb-2';
                    
                    const accessionLabel = document.createElement('span');
                    accessionLabel.className = 'input-group-text';
                    accessionLabel.textContent = data.accessionLabel || 'Accession';
                    
                    const callNumberInput = document.createElement('input');
                    callNumberInput.type = 'text';
                    callNumberInput.className = 'form-control call-number-input';
                    callNumberInput.name = 'call_number[]';
                    callNumberInput.value = data.callNumber || '';
                    callNumberInput.placeholder = 'Enter call number';
                    
                    // Create copy number label and input
                    const copyNumberLabel = document.createElement('span');
                    copyNumberLabel.className = 'input-group-text';
                    copyNumberLabel.textContent = 'Copy #';
                    
                    const copyNumberInput = document.createElement('input');
                    copyNumberInput.type = 'number';
                    copyNumberInput.className = 'form-control copy-number-input';
                    copyNumberInput.name = 'copy_number[]';
                    copyNumberInput.min = '1';
                    copyNumberInput.value = data.copyNumber || '';
                    copyNumberInput.style.width = '70px';
                    
                    const shelfLocationSelect = document.createElement('select');
                    shelfLocationSelect.className = 'form-control shelf-location-select';
                    shelfLocationSelect.name = 'shelf_locations[]';
                    
                    // Add shelf location options
                    const shelfOptions = [
                        ['TR', 'Teachers Reference'],
                        ['FIL', 'Filipiniana'],
                        ['CIR', 'Circulation'],
                        ['REF', 'Reference'],
                        ['SC', 'Special Collection'],
                        ['BIO', 'Biography'],
                        ['RES', 'Reserve'],
                        ['FIC', 'Fiction']
                    ];
                    
                    shelfOptions.forEach(([value, text]) => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = text;
                        if (value === data.shelfLocation) {
                            option.selected = true;
                        }
                        shelfLocationSelect.appendChild(option);
                    });
                    
                    // Apply the new order of elements
                    callNumberDiv.appendChild(accessionLabel);
                    callNumberDiv.appendChild(callNumberInput);
                    callNumberDiv.appendChild(copyNumberLabel);
                    callNumberDiv.appendChild(copyNumberInput);
                    callNumberDiv.appendChild(shelfLocationSelect);
                    callNumberContainer.appendChild(callNumberDiv);
                });
            } else if (callNumberContainer) {
                // If call number fields exist but empty (like after updateISBNFields), fill them in
                setTimeout(() => {
                    const callNumberContainers = callNumberContainer.querySelectorAll('.input-group');
                    formData['callNumberData'].forEach((data, index) => {
                        if (index < callNumberContainers.length) {
                            const container = callNumberContainers[index];
                            const callNumberInput = container.querySelector('.call-number-input');
                            const shelfLocationSelect = container.querySelector('.shelf-location-select');
                            const copyNumberInput = container.querySelector('.copy-number-input');
                            
                            if (callNumberInput) callNumberInput.value = data.callNumber || '';
                            if (shelfLocationSelect) shelfLocationSelect.value = data.shelfLocation || '';
                            if (copyNumberInput) copyNumberInput.value = data.copyNumber || '';
                        }
                    });
                }, 200);
            }
        }
        
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
            if (progressBar) {
                progressBar.style.width = formData['progressValue'];
                progressBar.setAttribute('aria-valuenow', parseInt(formData['progressValue']));
            }
        }
        
        // Restore completed tabs with improved selector handling
        if (formData['completedTabs'] && Array.isArray(formData['completedTabs'])) {
            formData['completedTabs'].forEach(tabId => {
                // Try different selector approaches to find the tab
                let tab = document.querySelector(`a#${tabId}`);
                if (!tab) tab = document.querySelector(`a[id="${tabId}"]`);
                if (!tab) tab = document.querySelector(`#formTabs .nav-link[href="#${tabId.replace('tab', 'proper')}"]`);
                if (!tab) tab = document.querySelector(`#formTabs .nav-link[href="#${tabId}"]`);
                if (!tab) tab = document.querySelector(`#formTabs .nav-link[id="${tabId}"]`);
                
                if (tab) {
                    tab.classList.add('completed');
                }
            });
        }
        
        // Validate all tabs on initial load to mark them as completed if needed
        validateAllTabs();
    }

    // Function to completely clear all form data from localStorage 
    window.clearAllFormData = function() {
        localStorage.removeItem(storageKey);
        localStorage.removeItem('formProgress');
        localStorage.removeItem('completedTabs');
        
        // Reset the form element
        if (form) form.reset();
        
        // Reset progress bar
        const progressBar = document.getElementById('formProgressBar');
        if (progressBar) {
            progressBar.style.width = '0%';
            progressBar.setAttribute('aria-valuenow', 0);
        }
        
        // Remove completed status from all tabs
        document.querySelectorAll('#formTabs .nav-link').forEach(tab => {
            tab.classList.remove('completed');
        });
        
        // Reset accession groups
        const accessionContainer = document.getElementById('accessionContainer');
        if (accessionContainer) {
            const firstGroup = accessionContainer.querySelector('.accession-group');
            if (firstGroup) {
                // Clear inputs
                const accessionInput = firstGroup.querySelector('.accession-input');
                const copiesInput = firstGroup.querySelector('.copies-input');
                if (accessionInput) accessionInput.value = '';
                if (copiesInput) copiesInput.value = '1';
                
                // Keep only the first group
                accessionContainer.innerHTML = '';
                accessionContainer.appendChild(firstGroup);
            }
        }
        
        // Clear call numbers and ISBN fields
        const callNumberContainer = document.getElementById('callNumberContainer');
        if (callNumberContainer) {
            callNumberContainer.innerHTML = '';
        }
        
        const isbnContainer = document.getElementById('isbnContainer');
        if (isbnContainer) {
            isbnContainer.innerHTML = '';
        }
        
        // Activate first tab
        const firstTab = document.querySelector('#formTabs .nav-link');
        if (firstTab && typeof $(firstTab).tab === 'function') {
            $(firstTab).tab('show');
        }
        
        console.log('All form data has been cleared');
    };

    // Function to validate all tabs and mark them as completed if all required fields are filled
    function validateAllTabs() {
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabPanes.forEach(pane => {
            const tabId = pane.id;
            const tab = document.querySelector(`a[href="#${tabId}"]`);
            if (!tab) return;
            
            // Check if all required fields in this tab are filled
            const requiredFields = pane.querySelectorAll('input[required], select[required], textarea[required]');
            let allFilled = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    allFilled = false;
                }
            });
            
            // If all required fields are filled, mark tab as completed
            if (allFilled && requiredFields.length > 0) {
                tab.classList.add('completed');
            }
        });
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
    
    // Validate tabs after a short delay to ensure all fields are properly loaded
    setTimeout(validateAllTabs, 500);

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

    // Helper function to create an accession group
    function createAccessionGroup(copyNumber) {
        const div = document.createElement('div');
        div.className = 'accession-group mb-3';
        div.innerHTML = `
            <div class="row">
                <div class="col-md-7">
                    <div class="form-group">
                        <label>Accession (Copy ${copyNumber})</label>
                        <input type="text" class="form-control accession-input" name="accession[]" 
                            placeholder="e.g., 2023-0001" required>
                        <small class="text-muted">Format: YYYY-NNNN</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Number of Copies</label>
                        <input type="number" class="form-control copies-input" name="number_of_copies[]" min="1" value="1" required>
                        <small class="text-muted">Auto-increments accession</small>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-center justify-content-center">
                    ${copyNumber > 1 ? '<button type="button" class="btn btn-danger btn-sm remove-accession"><i class="fas fa-trash"></i> Remove</button>' : ''}
                </div>
            </div>
            
            <!-- Details section will be populated by updateISBNFields -->
            <div class="accession-details"></div>
        `;
        return div;
    }
});
