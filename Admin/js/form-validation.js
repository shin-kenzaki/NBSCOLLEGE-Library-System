/**
 * Form validation functionality
 */
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('bookForm');
    
    // Replace SweetAlert validation with standard alert
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
    
    // Add event listener to the form
    form.addEventListener('submit', validateForm);
    
    // Single form submission handler
    form.onsubmit = function(e) {
        if (!validateForm(e)) {
            e.preventDefault();
            return false;
        }
        
        if (!confirm('Are you sure you want to add this book?')) {
            e.preventDefault();
            return false;
        }
        
        return true;
    };
    
    // Initialize file input display
    document.querySelectorAll('.custom-file-input').forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'Choose file';
            this.nextElementSibling.textContent = fileName;
        });
    });
});
