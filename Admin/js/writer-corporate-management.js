/**
 * Writer and Corporate Entity Management
 * Handles adding new writers and corporate entities via AJAX
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize writer management
    initWriterManagement();
    
    // Initialize corporate management
    initCorporateManagement();
});

/**
 * Writer Management Functions
 */
function initWriterManagement() {
    // Add writer button
    const addWriterBtn = document.getElementById('addNewWriter');
    if (addWriterBtn) {
        addWriterBtn.addEventListener('click', showAddWriterModal);
    }
    
    // Save writer button in modal
    const saveWriterBtn = document.getElementById('saveWriterBtn');
    if (saveWriterBtn) {
        saveWriterBtn.addEventListener('click', saveWriter);
    }
}

function showAddWriterModal() {
    // Reset form fields
    document.getElementById('writerForm').reset();
    
    // Show the modal
    $('#addWriterModal').modal('show');
}

function saveWriter() {
    // Get form data
    const firstname = document.getElementById('writerFirstname').value.trim();
    const middleInit = document.getElementById('writerMiddleInit').value.trim();
    const lastname = document.getElementById('writerLastname').value.trim();
    
    // Validate required fields
    if (!firstname || !lastname) {
        showAlert('error', 'First name and last name are required!');
        return;
    }
    
    // Prepare data for AJAX request
    const formData = {
        firstname: firstname,
        middle_init: middleInit,
        lastname: lastname
    };
    
    // Send AJAX request
    fetch('ajax/add_writers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify([formData])
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            $('#addWriterModal').modal('hide');
            
            // Show success message
            showAlert('success', 'Writer added successfully!');
            
            // Add the new writer to the select dropdown
            const writerSelect = document.getElementById('writerSelect');
            if (writerSelect && data.authors && data.authors.length > 0) {
                const newWriter = data.authors[0];
                const option = new Option(newWriter.name, newWriter.id);
                writerSelect.add(option);
                writerSelect.value = newWriter.id;
                
                // Trigger change event for Select2
                if ($.fn.select2) {
                    $(writerSelect).trigger('change');
                }
            }
        } else {
            showAlert('error', data.message || 'Error adding writer');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while saving the writer');
    });
}

/**
 * Corporate Management Functions
 */
function initCorporateManagement() {
    // Add corporate button
    const addCorporateBtn = document.getElementById('addNewCorporate');
    if (addCorporateBtn) {
        addCorporateBtn.addEventListener('click', showAddCorporateModal);
    }
    
    // Save corporate button in modal
    const saveCorporateBtn = document.getElementById('saveCorporateBtn');
    if (saveCorporateBtn) {
        saveCorporateBtn.addEventListener('click', saveCorporate);
    }
}

function showAddCorporateModal() {
    // Reset form fields
    document.getElementById('corporateForm').reset();
    
    // Show the modal
    $('#addCorporateModal').modal('show');
}

function saveCorporate() {
    // Get form data
    const name = document.getElementById('corporateName').value.trim();
    const type = document.getElementById('corporateType').value.trim();
    const location = document.getElementById('corporateLocation').value.trim();
    const description = document.getElementById('corporateDescription').value.trim();
    
    // Validate required fields
    if (!name || !type) {
        showAlert('error', 'Name and type are required!');
        return;
    }
    
    // Prepare data for AJAX request
    const formData = {
        name: name,
        type: type,
        location: location,
        description: description
    };
    
    // Send AJAX request
    fetch('ajax/add_corporate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            $('#addCorporateModal').modal('hide');
            
            // Show success message
            showAlert('success', 'Corporate entity added successfully!');
            
            // Add the new corporate to the select dropdown
            const corporateSelect = document.getElementById('corporateSelect');
            if (corporateSelect && data.corporate) {
                const newCorporate = data.corporate;
                const option = new Option(newCorporate.name, newCorporate.id);
                corporateSelect.add(option);
                corporateSelect.value = newCorporate.id;
                
                // Trigger change event for Select2
                if ($.fn.select2) {
                    $(corporateSelect).trigger('change');
                }
            }
        } else {
            showAlert('error', data.message || 'Error adding corporate entity');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while saving the corporate entity');
    });
}

/**
 * Utility Functions
 */
function showAlert(type, message) {
    // Use SweetAlert2 if available
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Success' : 'Error',
            text: message,
            timer: 3000,
            timerProgressBar: true
        });
    } else {
        // Fallback to standard alert
        alert(message);
    }
}
