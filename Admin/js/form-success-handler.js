/**
 * Converts modal dialogs to SweetAlert dialogs
 */
document.addEventListener("DOMContentLoaded", function() {
    // Author management with SweetAlert
    let authorFormHtml = '';
    
    // Reference to the original "Add New" button and replace its action
    const addAuthorBtn = document.querySelector('button[data-target="#addAuthorModal"]');
    if (addAuthorBtn) {
        // Create the HTML for the SweetAlert form
        authorFormHtml = `
            <div id="sweetAlertAuthorForm">
                <div id="sweetAlertAuthorContainer">
                    <div class="author-entry row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" class="form-control author-firstname" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Middle Initial</label>
                                <input type="text" class="form-control author-middleinit">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" class="form-control author-lastname" required>
                            </div>
                        </div>
                        <div class="col-md-1 remove-btn-container">
                            <button type="button" class="btn btn-danger btn-sm remove-author-entry">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm mt-2" id="sweetAlertAddAuthorEntry">
                    <i class="fas fa-plus"></i> Add Another Author
                </button>
            </div>
        `;
        
        // Override the click event of the original button
        addAuthorBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showAuthorSweetAlert();
        });
    }
    
    // Publisher management with SweetAlert
    let publisherFormHtml = '';
    
    // Reference to the original "Add New" button and replace its action
    const addPublisherBtn = document.querySelector('button[data-target="#addPublisherModal"]');
    if (addPublisherBtn) {
        // Create the HTML for the SweetAlert form
        publisherFormHtml = `
            <div id="sweetAlertPublisherForm">
                <div id="sweetAlertPublisherContainer">
                    <div class="publisher-entry row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Publisher Name</label>
                                <input type="text" class="form-control publisher-name" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Place of Publication</label>
                                <input type="text" class="form-control publisher-place" required>
                            </div>
                        </div>
                        <div class="col-md-1 remove-btn-container">
                            <button type="button" class="btn btn-danger btn-sm remove-publisher-entry">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm mt-2" id="sweetAlertAddPublisherEntry">
                    <i class="fas fa-plus"></i> Add Another Publisher
                </button>
            </div>
        `;
        
        // Override the click event of the original button
        addPublisherBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showPublisherSweetAlert();
        });
    }

    // Function to show the Author SweetAlert
    function showAuthorSweetAlert() {
        Swal.fire({
            title: 'Add New Authors',
            html: authorFormHtml,
            width: '800px',
            showCancelButton: true,
            cancelButtonText: 'Close',
            confirmButtonText: 'Save All Authors',
            focusConfirm: false,
            didOpen: () => {
                // Add event listener for "Add Another Author" button
                document.getElementById('sweetAlertAddAuthorEntry').addEventListener('click', function() {
                    const container = document.getElementById('sweetAlertAuthorContainer');
                    const newEntry = document.createElement('div');
                    newEntry.className = 'author-entry row mb-3';
                    newEntry.innerHTML = `
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" class="form-control author-firstname" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Middle Initial</label>
                                <input type="text" class="form-control author-middleinit">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" class="form-control author-lastname" required>
                            </div>
                        </div>
                        <div class="col-md-1 remove-btn-container">
                            <button type="button" class="btn btn-danger btn-sm remove-author-entry">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    container.appendChild(newEntry);
                });

                // Event delegation for remove buttons
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-author-entry') || e.target.closest('.remove-author-entry')) {
                        const container = document.getElementById('sweetAlertAuthorContainer');
                        if (container && container.children.length > 1) {
                            e.target.closest('.author-entry').remove();
                        } else {
                            Swal.showValidationMessage('At least one author entry is required.');
                        }
                    }
                });
            },
            preConfirm: () => {
                const authorEntries = document.querySelectorAll('#sweetAlertAuthorContainer .author-entry');
                const authorsData = [];
                let hasErrors = false;

                // Collect data from all author entries
                authorEntries.forEach(entry => {
                    const firstname = entry.querySelector('.author-firstname').value.trim();
                    const middle_init = entry.querySelector('.author-middleinit').value.trim();
                    const lastname = entry.querySelector('.author-lastname').value.trim();
                    
                    if (!firstname || !lastname) {
                        hasErrors = true;
                        return;
                    }
                    
                    authorsData.push({
                        firstname: firstname,
                        middle_init: middle_init,
                        lastname: lastname
                    });
                });
                
                if (hasErrors) {
                    Swal.showValidationMessage('First name and last name are required for all authors.');
                    return false;
                }
                
                if (authorsData.length === 0) {
                    Swal.showValidationMessage('Please add at least one author.');
                    return false;
                }
                
                return authorsData;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                saveAuthorsData(result.value);
            }
        });
    }

    // Function to save author data via AJAX
    function saveAuthorsData(authorsData) {
        // Show loading state
        Swal.fire({
            title: 'Saving Authors...',
            html: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // AJAX request to save all authors
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/add_writers.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Add all new authors to the select options
                        const authorSelect = document.getElementById('authorSelect');
                        const coAuthorsSelect = document.getElementById('coAuthorsSelect');
                        const editorsSelect = document.getElementById('editorsSelect');
                        
                        response.authors.forEach(author => {
                            const newOption = document.createElement('option');
                            newOption.value = author.id;
                            newOption.textContent = author.name;
                            
                            authorSelect.appendChild(newOption.cloneNode(true));
                            coAuthorsSelect.appendChild(newOption.cloneNode(true));
                            editorsSelect.appendChild(newOption.cloneNode(true));
                        });
                        
                        // Select the first new author in the author dropdown if no author is selected
                        if (!authorSelect.value && response.authors.length > 0) {
                            authorSelect.value = response.authors[0].id;
                        }
                        
                        // Success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: `Successfully added ${response.authors.length} author(s)!`,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Error adding authors',
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Error processing response: ' + e.message,
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Error adding authors',
                });
            }
        };
        xhr.send(JSON.stringify(authorsData));
    }

    // Function to show the Publisher SweetAlert
    function showPublisherSweetAlert() {
        Swal.fire({
            title: 'Add New Publishers',
            html: publisherFormHtml,
            width: '800px',
            showCancelButton: true,
            cancelButtonText: 'Close',
            confirmButtonText: 'Save All Publishers',
            focusConfirm: false,
            didOpen: () => {
                // Add event listener for "Add Another Publisher" button
                document.getElementById('sweetAlertAddPublisherEntry').addEventListener('click', function() {
                    const container = document.getElementById('sweetAlertPublisherContainer');
                    const newEntry = document.createElement('div');
                    newEntry.className = 'publisher-entry row mb-3';
                    newEntry.innerHTML = `
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Publisher Name</label>
                                <input type="text" class="form-control publisher-name" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Place of Publication</label>
                                <input type="text" class="form-control publisher-place" required>
                            </div>
                        </div>
                        <div class="col-md-1 remove-btn-container">
                            <button type="button" class="btn btn-danger btn-sm remove-publisher-entry">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    container.appendChild(newEntry);
                });

                // Event delegation for remove buttons
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-publisher-entry') || e.target.closest('.remove-publisher-entry')) {
                        const container = document.getElementById('sweetAlertPublisherContainer');
                        if (container && container.children.length > 1) {
                            e.target.closest('.publisher-entry').remove();
                        } else {
                            Swal.showValidationMessage('At least one publisher entry is required.');
                        }
                    }
                });
            },
            preConfirm: () => {
                const publisherEntries = document.querySelectorAll('#sweetAlertPublisherContainer .publisher-entry');
                const publishersData = [];
                let hasErrors = false;

                // Collect data from all publisher entries
                publisherEntries.forEach(entry => {
                    const publisher = entry.querySelector('.publisher-name').value.trim();
                    const place = entry.querySelector('.publisher-place').value.trim();
                    
                    if (!publisher || !place) {
                        hasErrors = true;
                        return;
                    }
                    
                    publishersData.push({
                        publisher: publisher,
                        place: place
                    });
                });
                
                if (hasErrors) {
                    Swal.showValidationMessage('Publisher name and place are required for all publishers.');
                    return false;
                }
                
                if (publishersData.length === 0) {
                    Swal.showValidationMessage('Please add at least one publisher.');
                    return false;
                }
                
                return publishersData;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                savePublishersData(result.value);
            }
        });
    }

    // Function to save publisher data via AJAX
    function savePublishersData(publishersData) {
        // Show loading state
        Swal.fire({
            title: 'Saving Publishers...',
            html: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // AJAX request to save all publishers
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/add_publishers.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Add all new publishers to the select options
                        const publisherSelect = document.getElementById('publisher');
                        
                        response.publishers.forEach(pub => {
                            // Check if this publisher is already in the dropdown
                            let exists = false;
                            for (let i = 0; i < publisherSelect.options.length; i++) {
                                if (publisherSelect.options[i].value === pub.publisher) {
                                    exists = true;
                                    break;
                                }
                            }
                            
                            if (!exists) {
                                const newOption = document.createElement('option');
                                newOption.value = pub.publisher;
                                newOption.textContent = `${pub.publisher} (${pub.place})`;
                                publisherSelect.appendChild(newOption);
                            }
                        });
                        
                        // Select the first new publisher in the dropdown if none is selected
                        if (!publisherSelect.value && response.publishers.length > 0) {
                            publisherSelect.value = response.publishers[0].publisher;
                        }
                        
                        // Success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: `Successfully added ${response.publishers.length} publisher(s)!`,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Error adding publishers',
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Error processing response: ' + e.message,
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Error adding publishers',
                });
            }
        };
        xhr.send(JSON.stringify(publishersData));
    }
});