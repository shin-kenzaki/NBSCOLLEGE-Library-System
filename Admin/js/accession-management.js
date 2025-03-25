/**
 * Accession management functionality
 */
document.addEventListener("DOMContentLoaded", function() {
    // Initialize call number container on page load
    updateISBNFields();
    
    // Consolidated event delegation for buttons
    document.addEventListener('click', function(e) {
        // Handle add accession button click
        if (e.target.closest('.add-accession')) {
            addAccessionGroup();
            return;
        }
        
        // Handle remove accession button click
        const removeButton = e.target.closest('.remove-accession');
        if (removeButton) {
            const accessionContainer = document.getElementById('accessionContainer');
            if (accessionContainer.children.length > 1) {
                // Store values from existing detail sections before removal
                const valuesMap = saveDetailValues();
                
                // Remove the accession group
                removeButton.closest('.accession-group').remove();
                
                // Update labels and call numbers
                updateAccessionLabels();
                updateCallNumbers();
                
                // Restore the saved values
                restoreDetailValues(valuesMap);
            } else {
                alert('At least one accession group is required.');
            }
        }
    });
    
    // Event listeners for accession changes
    document.addEventListener('input', function(e) {
        if (e.target && (e.target.classList.contains('copies-input') || e.target.classList.contains('accession-input'))) {
            updateISBNFields();
        }
    });
    
    // Add input validation for numbers only
    document.addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('accession-input')) {
            e.target.value = e.target.value.replace(/\D/g, ''); // Remove non-digits
        }
        
        // Validate ISBN format if needed
        if (e.target && e.target.name === 'isbn[]') {
            // Optional: Add ISBN validation logic here
            // e.target.value = e.target.value.replace(/[^\d-]/g, '');
        }
    });
    
    // Add event listener for cascading updates for call numbers
    document.addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('call-number-input')) {
            const callNumberInputs = document.querySelectorAll('.call-number-input');
            const index = Array.from(callNumberInputs).indexOf(e.target);
            
            for (let i = index + 1; i < callNumberInputs.length; i++) {
                callNumberInputs[i].value = callNumberInputs[index].value;
            }
        }
    });
    
    // Add event listener for cascading updates for shelf locations
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('shelf-location-select')) {
            const shelfLocationSelects = document.querySelectorAll('.shelf-location-select');
            const index = Array.from(shelfLocationSelects).indexOf(e.target);
            
            for (let i = index + 1; i < shelfLocationSelects.length; i++) {
                shelfLocationSelects[i].value = shelfLocationSelects[index].value;
            }
        }
    });
    
    // Check if we need to manually trigger the call number creation on initial page load
    setTimeout(function() {
        // If call number container is empty but we have accession groups, update the call numbers
        const callNumberContainer = document.getElementById('callNumberContainer');
        const accessionGroups = document.querySelectorAll('.accession-group');
        
        if (callNumberContainer && callNumberContainer.children.length === 0 && accessionGroups.length > 0) {
            console.log('Manually triggering call number creation for initial load');
            updateISBNFields();
            
            // If that fails, try the direct generator
            setTimeout(function() {
                if (callNumberContainer.children.length === 0 && typeof generateCallNumbersDirectly === 'function') {
                    generateCallNumbersDirectly();
                }
            }, 300);
        }
    }, 300);
});

// Create data attributes for easier form processing
function updateISBNFields() {
    console.log('Running updateISBNFields function with direct DOM manipulation');
    
    // Save existing values first
    const valuesMap = saveDetailValues();
    
    const isbnContainer = document.getElementById('isbnContainer');
    const callNumberContainer = document.getElementById('callNumberContainer');
    
    if (!callNumberContainer) {
        console.error('Call number container not found!');
        alert('Error: Call number container not found. Please refresh the page.');
        return;
    }
    
    // Always clear the containers to ensure fresh content
    console.log('Clearing containers...');
    isbnContainer.innerHTML = '';
    callNumberContainer.innerHTML = '';
    
    // Get all accession groups
    const accessionGroups = document.querySelectorAll('.accession-group');
    console.log(`Found ${accessionGroups.length} accession groups`);
    
    if (accessionGroups.length === 0) {
        callNumberContainer.innerHTML = '<div class="alert alert-warning">No accession groups found. Please add an accession number first.</div>';
        return;
    }
    
    // Track details across groups for comparison
    let detailsGroups = [];
    let totalCopiesByDetails = {};
    let startingCopyNumber = {};
    
    // First pass: collect all details
    accessionGroups.forEach((group, groupIndex) => {
        const accessionInput = group.querySelector('.accession-input').value;
        const copiesCount = parseInt(group.querySelector('.copies-input').value) || 1;
        
        // First remove any existing details section
        const existingDetails = group.querySelector('.accession-details');
        if (existingDetails) {
            existingDetails.remove();
        }
        
        // Create details section under each accession group
        const detailsDiv = document.createElement('div');
        detailsDiv.className = 'accession-details mt-3';
        
        // Add heading for the details
        const detailsLabel = document.createElement('h6');
        detailsLabel.className = 'text-muted mb-3';
        detailsLabel.textContent = `Details for Accession Group ${groupIndex + 1}`;
        detailsDiv.appendChild(detailsLabel);

        // Create a row for ISBN, series, volume, and edition inputs
        const rowDiv = document.createElement('div');
        rowDiv.className = 'row mb-3';

        // Create ISBN input
        const isbnDiv = document.createElement('div');
        isbnDiv.className = 'col-md-3';
        
        const isbnInput = document.createElement('input');
        isbnInput.type = 'text';
        isbnInput.className = 'form-control';
        isbnInput.name = 'isbn[]';
        isbnInput.placeholder = `ISBN`;
        isbnInput.dataset.groupIndex = groupIndex; // Add data attribute for identification
        
        isbnDiv.appendChild(isbnInput);
        rowDiv.appendChild(isbnDiv);

        // Create series input
        const seriesDiv = document.createElement('div');
        seriesDiv.className = 'col-md-3';

        const seriesInput = document.createElement('input');
        seriesInput.type = 'text';
        seriesInput.className = 'form-control';
        seriesInput.name = 'series[]';
        seriesInput.placeholder = `Series`;
        seriesInput.dataset.groupIndex = groupIndex; // Add data attribute for identification
        
        seriesDiv.appendChild(seriesInput);
        rowDiv.appendChild(seriesDiv);

        // Create volume input
        const volumeDiv = document.createElement('div');
        volumeDiv.className = 'col-md-3';

        const volumeInput = document.createElement('input');
        volumeInput.type = 'text';
        volumeInput.className = 'form-control';
        volumeInput.name = 'volume[]';
        volumeInput.placeholder = `Volume`;
        volumeInput.dataset.groupIndex = groupIndex; // Add data attribute for identification
        
        volumeDiv.appendChild(volumeInput);
        rowDiv.appendChild(volumeDiv);

        // Create edition input
        const editionDiv = document.createElement('div');
        editionDiv.className = 'col-md-3';

        const editionInput = document.createElement('input');
        editionInput.type = 'text';
        editionInput.className = 'form-control';
        editionInput.name = 'edition[]';
        editionInput.placeholder = `Edition`;
        editionInput.dataset.groupIndex = groupIndex; // Add data attribute for identification
        
        editionDiv.appendChild(editionInput);
        rowDiv.appendChild(editionDiv);

        detailsDiv.appendChild(rowDiv);
        
        // Add the details section after the accession group's row
        const accessionRow = group.querySelector('.row');
        accessionRow.after(detailsDiv);
        
        // Store this group's details for later comparison
        detailsGroups.push({
            groupIndex,
            isbn: isbnInput.value || '',
            series: seriesInput.value || '',
            volume: volumeInput.value || '',
            edition: editionInput.value || '',
            accession: accessionInput,
            copies: copiesCount
        });
    });
    
    // Second pass: determine copy numbers and create call number inputs
    detailsGroups.forEach((groupDetails, index) => {
        // Create a key for this group's details
        const detailsKey = `${groupDetails.isbn}|${groupDetails.series}|${groupDetails.volume}|${groupDetails.edition}`;
        
        // Check if we've seen this set of details before
        if (totalCopiesByDetails[detailsKey] === undefined) {
            // First time seeing these details, start copy number at 1
            totalCopiesByDetails[detailsKey] = 0;
            startingCopyNumber[detailsKey] = 1;
        }
        
        // Get the starting copy number for this group
        const startCopy = startingCopyNumber[detailsKey] + totalCopiesByDetails[detailsKey];
        
        // Update the total copies for this set of details
        totalCopiesByDetails[detailsKey] += groupDetails.copies;
        
        // Create heading for this accession group's call numbers
        const groupHeader = document.createElement('div');
        groupHeader.className = 'mb-2 text-muted small font-weight-bold';
        groupHeader.innerHTML = `Accession Group ${index + 1}: ${groupDetails.accession}`;
        callNumberContainer.appendChild(groupHeader);
        
        // Create call number inputs for this group
        for (let i = 0; i < groupDetails.copies; i++) {
            const currentAccession = calculateAccession(groupDetails.accession, i);
            // Start copy numbers at 1 for each accession group
            const copyNumber = i + 1;
            
            const callNumberDiv = document.createElement('div');
            callNumberDiv.className = 'input-group mb-2';
            callNumberDiv.dataset.accessionGroup = index;
            
            const accessionLabel = document.createElement('span');
            accessionLabel.className = 'input-group-text';
            accessionLabel.textContent = `Accession ${currentAccession}`;
            
            const callNumberInput = document.createElement('input');
            callNumberInput.type = 'text';
            callNumberInput.className = 'form-control call-number-input';
            callNumberInput.name = 'call_number[]';
            callNumberInput.placeholder = 'Enter call number';
            
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
                shelfLocationSelect.appendChild(option);
            });
            
            // Create copy number label and input (positioned between call number and shelf location)
            const copyNumberLabel = document.createElement('span');
            copyNumberLabel.className = 'input-group-text';
            copyNumberLabel.textContent = 'Copy #';
            
            const copyNumberInput = document.createElement('input');
            copyNumberInput.type = 'number';
            copyNumberInput.className = 'form-control copy-number-input';
            copyNumberInput.name = 'copy_number[]';
            copyNumberInput.min = '1';
            copyNumberInput.value = copyNumber;
            copyNumberInput.style.width = '70px';
            
            // New order of elements in the input group
            callNumberDiv.appendChild(accessionLabel);
            callNumberDiv.appendChild(callNumberInput); // Call number input
            callNumberDiv.appendChild(copyNumberLabel);
            callNumberDiv.appendChild(copyNumberInput); // Copy number input
            callNumberDiv.appendChild(shelfLocationSelect); // Shelf location select
            callNumberContainer.appendChild(callNumberDiv);
        }
    });
    
    // After creating call number fields, log the number created:
    console.log(`Created ${callNumberContainer.children.length} call number entries`);
    
    // After all processing, restore saved values
    restoreDetailValues(valuesMap);
    
    // Trigger form autosave to persist the generated call numbers
    if (typeof saveFormData === 'function') {
        setTimeout(saveFormData, 100);
    }

    // After creating all call number fields, ensure visibility:
    if (callNumberContainer.children.length === 0) {
        console.error('Failed to create call number fields during normal process');
        callNumberContainer.innerHTML = '<div class="alert alert-danger">Error: Call number generation failed. Please try again or refresh the page.</div>';
    } else {
        console.log(`Successfully created ${callNumberContainer.children.length} call number elements`);
    }
}

function calculateAccession(baseAccession, increment) {
    if (!baseAccession) return '(undefined)';
    
    // Handle formats like "2023-0001" or "2023-001" or just "0001"
    const match = baseAccession.match(/^(.*?)(\d+)$/);
    if (!match) return baseAccession;
    
    const prefix = match[1]; // Everything before the number
    const num = parseInt(match[2]); // The number part
    const width = match[2].length; // Original width of the number
    
    // Calculate new number and pad with zeros to maintain original width
    const newNum = (num + increment).toString().padStart(width, '0');
    
    return prefix + newNum;
}

// Function to add a new accession group
function addAccessionGroup() {
    const accessionContainer = document.getElementById('accessionContainer');
    const groups = accessionContainer.querySelectorAll('.accession-group');
    const newIndex = groups.length;
    
    // Create new accession group
    const newGroup = document.createElement('div');
    newGroup.className = 'accession-group mb-3';
    newGroup.innerHTML = `
        <div class="row">
            <div class="col-md-7">
                <div class="form-group">
                    <label>Accession (Copy ${newIndex + 1})</label>
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
                <button type="button" class="btn btn-danger btn-sm remove-accession">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </div>
        
        <!-- Details section - initially empty, will be populated by updateISBNFields -->
        <div class="accession-details"></div>
    `;
    
    accessionContainer.appendChild(newGroup);
    
    // Save current values
    const valuesMap = saveDetailValues();
    
    // Update labels and regenerate details
    updateAccessionLabels();
    updateISBNFields();
    
    // Restore saved values 
    restoreDetailValues(valuesMap);
}

// Initialize the first accession group with its own details section
function initializeAccessionGroups() {
    const firstGroup = document.querySelector('.accession-group');
    if (firstGroup) {
        // Remove any existing details to avoid duplicates
        const existingDetails = firstGroup.querySelector('.accession-details');
        if (existingDetails) {
            existingDetails.innerHTML = '';
        } else {
            // Create the container if it doesn't exist
            const detailsDiv = document.createElement('div');
            detailsDiv.className = 'accession-details';
            firstGroup.appendChild(detailsDiv);
        }
        
        // Let updateISBNFields populate the details section
        updateISBNFields();
    }
}

// Update accession labels after removal
function updateAccessionLabels() {
    const groups = document.querySelectorAll('.accession-group');
    groups.forEach((group, index) => {
        const label = group.querySelector('label');
        if (label) {
            label.textContent = `Accession (Copy ${index + 1})`;
        }
    });
}

// Create a function to save the current values of all detail fields
function saveDetailValues() {
    const valuesMap = {};
    
    // Get all accession groups
    const accessionGroups = document.querySelectorAll('.accession-group');
    
    accessionGroups.forEach((group, index) => {
        // Find inputs in this group's details section
        const isbnInput = group.querySelector('input[name^="isbn"]');
        const seriesInput = group.querySelector('input[name^="series"]');
        const volumeInput = group.querySelector('input[name^="volume"]');
        const editionInput = group.querySelector('input[name^="edition"]');
        
        if (isbnInput && seriesInput && volumeInput && editionInput) {
            valuesMap[index] = {
                isbn: isbnInput.value,
                series: seriesInput.value,
                volume: volumeInput.value,
                edition: editionInput.value
            };
        }
    });
    
    return valuesMap;
}

// Function to restore values after operations that might clear them
function restoreDetailValues(valuesMap) {
    const accessionGroups = document.querySelectorAll('.accession-group');
    
    accessionGroups.forEach((group, index) => {
        // Only restore if we have saved values for this index
        if (valuesMap[index]) {
            const isbnInput = group.querySelector('input[name^="isbn"]');
            const seriesInput = group.querySelector('input[name^="series"]');
            const volumeInput = group.querySelector('input[name^="volume"]');
            const editionInput = group.querySelector('input[name^="edition"]');
            
            if (isbnInput) isbnInput.value = valuesMap[index].isbn;
            if (seriesInput) seriesInput.value = valuesMap[index].series;
            if (volumeInput) volumeInput.value = valuesMap[index].volume;
            if (editionInput) editionInput.value = valuesMap[index].edition;
        }
    });
}

// Update only the call number container without affecting details
function updateCallNumbers() {
    const callNumberContainer = document.getElementById('callNumberContainer');
    callNumberContainer.innerHTML = '';
    
    // Get all accession groups
    const accessionGroups = document.querySelectorAll('.accession-group');
    
    // Track details across groups for comparison
    let detailsGroups = [];
    let totalCopiesByDetails = {};
    let startingCopyNumber = {};
    
    // First pass: collect all details
    accessionGroups.forEach((group, groupIndex) => {
        const accessionInput = group.querySelector('.accession-input').value;
        const copiesCount = parseInt(group.querySelector('.copies-input').value) || 1;
        
        const isbnInput = group.querySelector('input[name^="isbn"]');
        const seriesInput = group.querySelector('input[name^="series"]');
        const volumeInput = group.querySelector('input[name^="volume"]');
        const editionInput = group.querySelector('input[name^="edition"]');
        
        // Store this group's details for later comparison
        detailsGroups.push({
            groupIndex,
            isbn: isbnInput ? isbnInput.value || '' : '',
            series: seriesInput ? seriesInput.value || '' : '',
            volume: volumeInput ? volumeInput.value || '' : '',
            edition: editionInput ? editionInput.value || '' : '',
            accession: accessionInput,
            copies: copiesCount
        });
    });
    
    // Second pass: determine copy numbers and create call number inputs
    detailsGroups.forEach((groupDetails, index) => {
        // Create a key for this group's details
        const detailsKey = `${groupDetails.isbn}|${groupDetails.series}|${groupDetails.volume}|${groupDetails.edition}`;
        
        // Check if we've seen this set of details before
        if (totalCopiesByDetails[detailsKey] === undefined) {
            // First time seeing these details, start copy number at 1
            totalCopiesByDetails[detailsKey] = 0;
            startingCopyNumber[detailsKey] = 1;
        }
        
        // Get the starting copy number for this group
        const startCopy = startingCopyNumber[detailsKey] + totalCopiesByDetails[detailsKey];
        
        // Update the total copies for this set of details
        totalCopiesByDetails[detailsKey] += groupDetails.copies;
        
        // Create heading for this accession group's call numbers
        const groupHeader = document.createElement('div');
        groupHeader.className = 'mb-2 text-muted small font-weight-bold';
        groupHeader.innerHTML = `Accession Group ${index + 1}: ${groupDetails.accession}`;
        callNumberContainer.appendChild(groupHeader);
        
        // Create call number inputs for this group
        for (let i = 0; i < groupDetails.copies; i++) {
            const currentAccession = calculateAccession(groupDetails.accession, i);
            // Start copy numbers at 1 for each accession group
            const copyNumber = i + 1;
            
            const callNumberDiv = document.createElement('div');
            callNumberDiv.className = 'input-group mb-2';
            callNumberDiv.dataset.accessionGroup = index;
            
            const accessionLabel = document.createElement('span');
            accessionLabel.className = 'input-group-text';
            accessionLabel.textContent = `Accession ${currentAccession}`;
            
            const callNumberInput = document.createElement('input');
            callNumberInput.type = 'text';
            callNumberInput.className = 'form-control call-number-input';
            callNumberInput.name = 'call_number[]';
            callNumberInput.placeholder = 'Enter call number';
            
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
                shelfLocationSelect.appendChild(option);
            });
            
            // Create copy number label and input (positioned between call number and shelf location)
            const copyNumberLabel = document.createElement('span');
            copyNumberLabel.className = 'input-group-text';
            copyNumberLabel.textContent = 'Copy #';
            
            const copyNumberInput = document.createElement('input');
            copyNumberInput.type = 'number';
            copyNumberInput.className = 'form-control copy-number-input';
            copyNumberInput.name = 'copy_number[]';
            copyNumberInput.min = '1';
            copyNumberInput.value = copyNumber;
            copyNumberInput.style.width = '70px';
            
            // New order of elements in the input group
            callNumberDiv.appendChild(accessionLabel);
            callNumberDiv.appendChild(callNumberInput); // Call number input
            callNumberDiv.appendChild(copyNumberLabel);
            callNumberDiv.appendChild(copyNumberInput); // Copy number input
            callNumberDiv.appendChild(shelfLocationSelect); // Shelf location select
            callNumberContainer.appendChild(callNumberDiv);
        }
    });
    
    // Trigger form autosave to persist the updated call numbers
    if (typeof saveFormData === 'function') {
        setTimeout(saveFormData, 100);
    }
}

// Create a direct function to generate call numbers immediately
function forceGenerateCallNumbers() {
    console.log('Force generating call numbers');
    const callNumberContainer = document.getElementById('callNumberContainer');
    const accessionContainer = document.getElementById('accessionContainer');
    
    if (!callNumberContainer || !accessionContainer) {
        console.error('Required containers not found');
        return;
    }
    
    // Clear any existing content
    callNumberContainer.innerHTML = '';
    
    // Get all accession groups
    const accessionGroups = accessionContainer.querySelectorAll('.accession-group');
    console.log(`Found ${accessionGroups.length} accession groups for direct generation`);
    
    // Process each accession group
    accessionGroups.forEach((group, groupIndex) => {
        const accessionInput = group.querySelector('.accession-input');
        const copiesInput = group.querySelector('.copies-input');
        
        if (!accessionInput || !copiesInput) {
            console.error('Required input fields not found in accession group');
            return;
        }
        
        const accession = accessionInput.value || `ACC-${groupIndex+1}`;
        const copies = parseInt(copiesInput.value) || 1;
        
        // Create header for this group
        const groupHeader = document.createElement('div');
        groupHeader.className = 'mb-2 text-muted small font-weight-bold';
        groupHeader.innerHTML = `Accession Group ${groupIndex + 1}: ${accession}`;
        callNumberContainer.appendChild(groupHeader);
        
        // Create input fields for each copy
        for (let i = 0; i < copies; i++) {
            createCallNumberRow(callNumberContainer, accession, i, groupIndex);
        }
    });
}

// Helper function to create a single call number row
function createCallNumberRow(container, baseAccession, increment, groupIndex) {
    const currentAccession = calculateAccession(baseAccession, increment);
    const copyNumber = increment + 1;
    
    const callNumberDiv = document.createElement('div');
    callNumberDiv.className = 'input-group mb-2';
    callNumberDiv.dataset.accessionGroup = groupIndex;
    
    const accessionLabel = document.createElement('span');
    accessionLabel.className = 'input-group-text';
    accessionLabel.textContent = `Accession ${currentAccession}`;
    
    const callNumberInput = document.createElement('input');
    callNumberInput.type = 'text';
    callNumberInput.className = 'form-control call-number-input';
    callNumberInput.name = 'call_number[]';
    callNumberInput.placeholder = 'Enter call number';
    
    const copyNumberLabel = document.createElement('span');
    copyNumberLabel.className = 'input-group-text';
    copyNumberLabel.textContent = 'Copy #';
    
    const copyNumberInput = document.createElement('input');
    copyNumberInput.type = 'number';
    copyNumberInput.className = 'form-control copy-number-input';
    copyNumberInput.name = 'copy_number[]';
    copyNumberInput.min = '1';
    copyNumberInput.value = copyNumber;
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
        if (value === 'CIR') option.selected = true;
        shelfLocationSelect.appendChild(option);
    });
    
    // Assemble the input group
    callNumberDiv.appendChild(accessionLabel);
    callNumberDiv.appendChild(callNumberInput);
    callNumberDiv.appendChild(copyNumberLabel);
    callNumberDiv.appendChild(copyNumberInput);
    callNumberDiv.appendChild(shelfLocationSelect);
    container.appendChild(callNumberDiv);
}

// Initialize everything at page load
document.addEventListener("DOMContentLoaded", function() {
    // Call the standard initialization first
    updateISBNFields();
    
    // If for some reason call numbers aren't generated, force them after a delay
    setTimeout(function() {
        const callNumberContainer = document.getElementById('callNumberContainer');
        if (callNumberContainer && callNumberContainer.children.length === 0) {
            console.log('No call numbers found after initial load, forcing generation');
            forceGenerateCallNumbers();
        }
    }, 500);
    
    // ...existing code...
    
    // Add a button click handler for local-info-tab to ensure call numbers are shown
    document.getElementById('local-info-tab').addEventListener('click', function() {
        setTimeout(function() {
            const callNumberContainer = document.getElementById('callNumberContainer');
            if (callNumberContainer && callNumberContainer.children.length === 0) {
                console.log('No call numbers found when tab activated, forcing generation');
                forceGenerateCallNumbers();
            }
        }, 100);
    });
});
