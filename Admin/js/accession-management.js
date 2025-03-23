/**
 * Accession management functionality
 */
document.addEventListener("DOMContentLoaded", function() {
    // Initialize call number container on page load
    updateISBNFields();
    
    // Add accession group handler
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('add-accession')) {
            const accessionContainer = document.getElementById('accessionContainer');
            const groupCount = accessionContainer.children.length + 1;
            
            const newGroup = document.createElement('div');
            newGroup.className = 'accession-group mb-3';
            newGroup.innerHTML = `
                <div class="row">
                    <div class="col-md-7">
                        <div class="form-group">
                            <label>Accession (Copy ${groupCount})</label>
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
                    <div class="col-md-2 remove-btn-container">
                        <button type="button" class="btn btn-danger btn-sm remove-accession mb-2">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            accessionContainer.appendChild(newGroup);
            updateISBNFields();
        }
        
        if (e.target && e.target.classList.contains('remove-accession')) {
            const accessionContainer = document.getElementById('accessionContainer');
            if (accessionContainer.children.length > 1) {
                e.target.closest('.accession-group').remove();
                updateISBNFields();
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
});

function updateISBNFields() {
    const isbnContainer = document.getElementById('isbnContainer');
    const callNumberContainer = document.getElementById('callNumberContainer');
    isbnContainer.innerHTML = '';
    callNumberContainer.innerHTML = '';
    
    // Get all accession groups
    const accessionGroups = document.querySelectorAll('.accession-group');
    
    accessionGroups.forEach((group, groupIndex) => {
        const accessionInput = group.querySelector('.accession-input').value;
        const copiesCount = parseInt(group.querySelector('.copies-input').value) || 1;
        
        // Create a row for ISBN, series, volume, and edition inputs
        const rowDiv = document.createElement('div');
        rowDiv.className = 'row mb-3';

        // Create label for the row
        const labelDiv = document.createElement('div');
        labelDiv.className = 'col-12';
        const label = document.createElement('label');
        label.textContent = `Details for Accession Group ${groupIndex + 1}`;
        labelDiv.appendChild(label);
        rowDiv.appendChild(labelDiv);

        // Create ISBN input (in Publication tab)
        const isbnDiv = document.createElement('div');
        isbnDiv.className = 'col-md-3';
        
        const isbnInput = document.createElement('input');
        isbnInput.type = 'text';
        isbnInput.className = 'form-control';
        isbnInput.name = 'isbn[]';
        isbnInput.placeholder = `ISBN`;
        
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
        
        editionDiv.appendChild(editionInput);
        rowDiv.appendChild(editionDiv);

        isbnContainer.appendChild(rowDiv);
        
        // Create call number inputs (in Local Information tab)
        const groupLabel = document.createElement('h6');
        groupLabel.className = 'mt-3 mb-2';
        groupLabel.textContent = `Call Numbers for Accession Group ${groupIndex + 1}`;
        callNumberContainer.appendChild(groupLabel);
        
        for (let i = 0; i < copiesCount; i++) {
            const currentAccession = calculateAccession(accessionInput, i);
            
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
            
            callNumberDiv.appendChild(accessionLabel);
            callNumberDiv.appendChild(callNumberInput);
            callNumberDiv.appendChild(shelfLocationSelect);
            callNumberContainer.appendChild(callNumberDiv);
        }
    });
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
