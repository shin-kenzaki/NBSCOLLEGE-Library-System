/**
 * Fallback for call number generation in case the main implementation fails
 */
document.addEventListener('DOMContentLoaded', function() {
    // Wait for DOM to be fully loaded
    setTimeout(function() {
        const callNumberContainer = document.getElementById('callNumberContainer');
        const accessionContainer = document.getElementById('accessionContainer');
        
        // Only run if call number container exists and is empty
        if (callNumberContainer && callNumberContainer.children.length === 0 && accessionContainer) {
            console.log('Call number fallback mechanism activated');
            
            // Try to create call numbers manually
            const accessionGroups = accessionContainer.querySelectorAll('.accession-group');
            
            if (accessionGroups.length > 0) {
                accessionGroups.forEach((group, groupIndex) => {
                    const accessionInput = group.querySelector('.accession-input');
                    const copiesInput = group.querySelector('.copies-input');
                    
                    if (accessionInput && copiesInput) {
                        const accessionValue = accessionInput.value || `ACC-${groupIndex + 1}`;
                        const copiesCount = parseInt(copiesInput.value) || 1;
                        
                        // Create heading for this group
                        const groupHeader = document.createElement('div');
                        groupHeader.className = 'mb-2 text-muted small font-weight-bold';
                        groupHeader.innerHTML = `Accession Group ${groupIndex + 1}: ${accessionValue}`;
                        callNumberContainer.appendChild(groupHeader);
                        
                        // Create call number entries for each copy
                        for (let i = 0; i < copiesCount; i++) {
                            // Calculate accession with increment if possible
                            let currentAccession = accessionValue;
                            if (i > 0) {
                                // Simple increment logic
                                if (/\d+$/.test(accessionValue)) {
                                    const base = accessionValue.replace(/\d+$/, '');
                                    const num = parseInt(accessionValue.match(/\d+$/)[0]);
                                    currentAccession = base + (num + i).toString().padStart(accessionValue.match(/\d+$/)[0].length, '0');
                                }
                            }
                            
                            // Create call number input group
                            const callNumberDiv = document.createElement('div');
                            callNumberDiv.className = 'input-group mb-2';
                            
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
                            callNumberContainer.appendChild(callNumberDiv);
                        }
                    }
                });
                
                console.log('Fallback successfully created call number fields');
            }
        }
    }, 1000); // Wait 1 second to ensure other scripts have run
    
    // Listen for changes on accession inputs and update call numbers if needed
    document.addEventListener('input', function(e) {
        if (e.target && (e.target.classList.contains('accession-input') || e.target.classList.contains('copies-input'))) {
            setTimeout(function() {
                const callNumberContainer = document.getElementById('callNumberContainer');
                if (callNumberContainer && callNumberContainer.children.length === 0) {
                    console.log('Accession input changed, call number container empty - activating fallback');
                    
                    // Try direct generator first
                    if (typeof generateCallNumbersDirectly === 'function') {
                        generateCallNumbersDirectly();
                    } else {
                        // Otherwise use fallback generation logic
                        const accessionContainer = document.getElementById('accessionContainer');
                        const accessionGroups = accessionContainer.querySelectorAll('.accession-group');
                        
                        if (accessionGroups.length > 0) {
                            // Use the existing fallback logic...
                            console.log('Using fallback logic to generate call numbers');
                        }
                    }
                }
            }, 500);
        }
    });
});
