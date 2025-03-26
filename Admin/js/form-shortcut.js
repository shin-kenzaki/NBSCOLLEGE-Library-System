document.addEventListener("DOMContentLoaded", function() {
    // Initialize tabs
    var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'));
    triggerTabList.forEach(function(triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener("click", function(event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });

    // Form validation function
    function validateForm(e) {
        const accessionInputs = document.querySelectorAll('.accession-input');
        let hasError = false;
        let errorMessage = '';
        
        accessionInputs.forEach(input => {
            if (!input.value.trim()) {
                hasError = true;
                errorMessage = 'Please fill in all accession fields before submitting.';
            } else if (!/^\d+$/.test(input.value.trim())) {
                hasError = true;
                errorMessage = 'Accession numbers must contain only digits (0-9).';
            }
        });
        
        if (hasError) {
            e.preventDefault();
            alert(errorMessage);
            return false;
        }
        return true;
    }

    // Update ISBN and call number fields
    function updateISBNFields() {
        const accessionContainer = document.getElementById('accessionContainer');
        const callNumberContainer = document.getElementById('callNumberContainer');
        // ... rest of the function implementation
    }

    // Calculate accession number
    function calculateAccession(baseAccession, increment) {
        if (!baseAccession) return '';
        const match = baseAccession.match(/^(.*?)(\d+)$/);
        if (!match) return baseAccession;
        
        const prefix = match[1];
        const num = parseInt(match[2]);
        const width = match[2].length;
        return prefix + (num + increment).toString().padStart(width, '0');
    }

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
                            <input type="number" class="form-control copies-input" name="number_of_copies[]" 
                                min="1" value="1" required>
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

        // Remove accession group handler
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

    // Event listeners
    document.addEventListener('input', function(e) {
        if (e.target && (e.target.classList.contains('copies-input') || 
                        e.target.classList.contains('accession-input'))) {
            updateISBNFields();
        }
        
        // Modified input validation for accession input
        if (e.target && e.target.classList.contains('accession-input')) {
            // Allow numbers, dashes, and letters for more flexible accession numbers
            if (!/^[A-Za-z0-9\-]*$/.test(e.target.value)) {
                e.target.value = e.target.value.replace(/[^A-Za-z0-9\-]/g, '');
            }
        }
        
        // Add logic to update the call number format when call numbers are edited
        if (e.target && e.target.classList.contains('call-number-input')) {
            // If formatCallNumberDisplay exists, use it
            if (typeof formatCallNumberDisplay === 'function') {
                formatCallNumberDisplay(e.target);
                
                // Also cascade to other call number inputs
                const callNumberInputs = document.querySelectorAll('.call-number-input');
                const index = Array.from(callNumberInputs).indexOf(e.target);
                const baseCallNumber = e.target.value.trim();
                
                // Update all subsequent call numbers with the same base
                for (let i = index + 1; i < callNumberInputs.length; i++) {
                    callNumberInputs[i].value = baseCallNumber;
                    formatCallNumberDisplay(callNumberInputs[i]);
                }
            }
        }
        
        // Add tracking for ISBN, series, volume, and edition changes
        if (e.target && (
            e.target.name === 'isbn[]' || 
            e.target.name === 'series[]' || 
            e.target.name === 'volume[]' || 
            e.target.name === 'edition[]'
        )) {
            updateISBNFields();
            
            // Also update call number format if volume changes
            if (e.target.name === 'volume[]' && typeof formatCallNumberDisplay === 'function') {
                document.querySelectorAll('.call-number-input').forEach(input => {
                    formatCallNumberDisplay(input);
                });
            }
        }
    });

    // Form submission handler - modified to capture formatted call numbers
    document.getElementById('bookForm').addEventListener('submit', function(e) {
        if (!validateForm(e)) {
            e.preventDefault();
            return false;
        }
        
        // Format call numbers before submission
        const callNumberInputs = document.querySelectorAll('.call-number-input');
        callNumberInputs.forEach(input => {
            // If the input has a formatted call number data attribute, use it
            if (input.dataset.formattedCallNumber) {
                input.value = input.dataset.formattedCallNumber;
            } else {
                // Otherwise try to format it on the fly
                const container = input.closest('.input-group');
                if (container) {
                    const baseCallNumber = input.value.trim();
                    if (baseCallNumber) {
                        const shelfSelect = container.querySelector('.shelf-location-select');
                        const copyInput = container.querySelector('.copy-number-input');
                        const publishYear = document.getElementById('publish_date')?.value || '';
                        
                        // Find the volume for this group
                        let volume = '';
                        const accessionGroup = container.closest('[data-accession-group]');
                        if (accessionGroup) {
                            const groupIndex = accessionGroup.dataset.accessionGroup;
                            const volumeInputs = document.querySelectorAll('input[name="volume[]"]');
                            if (volumeInputs.length > groupIndex && volumeInputs[groupIndex].value) {
                                volume = 'vol' + volumeInputs[groupIndex].value;
                            }
                        }
                        
                        if (shelfSelect && copyInput) {
                            // Create the formatted call number
                            const shelf = shelfSelect.value;
                            const copy = 'c' + copyInput.value;
                            
                            // Build the final call number
                            const parts = [shelf, baseCallNumber, publishYear];
                            if (volume) parts.push(volume);
                            parts.push(copy);
                            
                            input.value = parts.join(' ');
                            console.log('Set call number to:', input.value);
                        }
                    }
                }
            }
        });
        
        // Log all call numbers being submitted for debugging
        console.log('Submitting call numbers:', Array.from(callNumberInputs).map(i => i.value));
        
        // Verify accession details are captured
        const accessionGroups = document.querySelectorAll('.accession-group');
        let isValid = true;
        
        accessionGroups.forEach(group => {
            const isbnInput = group.querySelector('input[name="isbn[]"]');
            if (isbnInput && !isbnInput.value.trim()) {
                // If ISBN validation is required, uncomment this
                // isValid = false;
                // isbnInput.classList.add('is-invalid');
            }
        });
        
        if (!isValid) {
            alert('Please complete all required accession details');
            e.preventDefault();
            return false;
        }
        
        // Remove the old confirmation dialog - SweetAlert handles this now
        return true;
    });

    // Call number formatting and management
    function formatCallNumber() {
        // ... existing formatCallNumber code ...
    }

    function updateCopyNumbers() {
        // ... existing updateCopyNumbers code ...
    }

    // Initialize form
    updateISBNFields();
});