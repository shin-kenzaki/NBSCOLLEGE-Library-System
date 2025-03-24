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
        
        // Input validation for numbers only
        if (e.target && e.target.classList.contains('accession-input')) {
            e.target.value = e.target.value.replace(/\D/g, '');
        }
    });

    // Form submission handler
    document.getElementById('bookForm').addEventListener('submit', function(e) {
        if (!validateForm(e)) {
            e.preventDefault();
            return false;
        }
        
        if (!confirm('Are you sure you want to add this book?')) {
            e.preventDefault();
            return false;
        }
        
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