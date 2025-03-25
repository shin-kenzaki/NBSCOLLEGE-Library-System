/**
 * Direct Call Number Generator - works independently when all else fails
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Direct call number generator loaded');
    
    // Function to directly generate call numbers without dependency on other scripts
    window.generateCallNumbersDirectly = function() {
        console.log('Direct call number generation executing...');
        
        const callNumberContainer = document.getElementById('callNumberContainer');
        const accessionContainer = document.getElementById('accessionContainer');
        
        if (!callNumberContainer || !accessionContainer) {
            console.error('Essential containers missing');
            return;
        }
        
        // Get all accession groups
        const accessionGroups = accessionContainer.querySelectorAll('.accession-group');
        if (accessionGroups.length === 0) {
            callNumberContainer.innerHTML = '<div class="alert alert-warning">No accession groups found. Please add accession information first.</div>';
            return;
        }
        
        // Clear container for fresh content
        callNumberContainer.innerHTML = '';
        
        // Process each accession group
        accessionGroups.forEach((group, groupIndex) => {
            const accessionInput = group.querySelector('.accession-input');
            const copiesInput = group.querySelector('.copies-input');
            
            if (!accessionInput || !copiesInput) {
                console.error('Missing inputs in accession group');
                return;
            }
            
            // Get values (or use defaults if empty)
            const accessionValue = accessionInput.value || `ACC-${groupIndex+1}`;
            const copies = parseInt(copiesInput.value) || 1;
            
            // Create group header
            const groupHeader = document.createElement('div');
            groupHeader.className = 'mb-2 text-muted small font-weight-bold';
            groupHeader.innerHTML = `Accession Group ${groupIndex + 1}: ${accessionValue}`;
            callNumberContainer.appendChild(groupHeader);
            
            // Generate call number fields for each copy
            for (let i = 0; i < copies; i++) {
                // Calculate incremented accession number
                let currentAccession = accessionValue;
                if (i > 0 && /\d+$/.test(accessionValue)) {
                    const match = accessionValue.match(/^(.*?)(\d+)$/);
                    if (match) {
                        const prefix = match[1];
                        const num = parseInt(match[2]);
                        const width = match[2].length;
                        currentAccession = prefix + (num + i).toString().padStart(width, '0');
                    }
                }
                
                // Create a call number row
                const row = document.createElement('div');
                row.className = 'input-group mb-2';
                row.dataset.accessionGroup = groupIndex;
                
                // Create elements
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
                copyNumberInput.value = i + 1;
                copyNumberInput.style.width = '70px';
                
                const shelfLocationSelect = document.createElement('select');
                shelfLocationSelect.className = 'form-control shelf-location-select';
                shelfLocationSelect.name = 'shelf_locations[]';
                
                // Add shelf locations
                [
                    ['TR', 'Teachers Reference'],
                    ['FIL', 'Filipiniana'],
                    ['CIR', 'Circulation'],
                    ['REF', 'Reference'],
                    ['SC', 'Special Collection'],
                    ['BIO', 'Biography'],
                    ['RES', 'Reserve'],
                    ['FIC', 'Fiction']
                ].forEach(([value, text]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = text;
                    if (value === 'CIR') option.selected = true;
                    shelfLocationSelect.appendChild(option);
                });
                
                // Assemble the row
                row.appendChild(accessionLabel);
                row.appendChild(callNumberInput);
                row.appendChild(copyNumberLabel);
                row.appendChild(copyNumberInput);
                row.appendChild(shelfLocationSelect);
                callNumberContainer.appendChild(row);
            }
        });
        
        console.log('Direct call number generation complete');
    };
    
    // Automatically check and generate call numbers when accession inputs change
    document.addEventListener('input', function(e) {
        if (e.target && (e.target.classList.contains('accession-input') || e.target.classList.contains('copies-input'))) {
            setTimeout(function() {
                const callNumberContainer = document.getElementById('callNumberContainer');
                if (callNumberContainer && callNumberContainer.children.length === 0) {
                    generateCallNumbersDirectly();
                }
            }, 300);
        }
    });
    
    // Add click handler for the tab
    const localInfoTab = document.getElementById('local-info-tab');
    if (localInfoTab) {
        localInfoTab.addEventListener('click', function() {
            setTimeout(function() {
                const callNumberContainer = document.getElementById('callNumberContainer');
                if (callNumberContainer && callNumberContainer.children.length === 0) {
                    generateCallNumbersDirectly();
                }
            }, 300);
        });
    }
    
    // Check if we need to generate call numbers on initial load
    setTimeout(function() {
        const callNumberContainer = document.getElementById('callNumberContainer');
        const accessionGroups = document.querySelectorAll('.accession-group');
        if (callNumberContainer && callNumberContainer.children.length === 0 && accessionGroups.length > 0) {
            generateCallNumbersDirectly();
        }
    }, 800);
    
    // Export the function globally
    window.setupManualCallNumberGeneration = function() {
        const callNumberContainer = document.getElementById('callNumberContainer');
        if (callNumberContainer && callNumberContainer.children.length === 0) {
            generateCallNumbersDirectly();
        }
    };
});
