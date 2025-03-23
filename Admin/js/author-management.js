/**
 * Author management functionality
 */
document.addEventListener("DOMContentLoaded", function() {
    // Add author entry functionality
    document.getElementById('addAuthorEntry').addEventListener('click', function() {
        const authorEntriesContainer = document.getElementById('authorEntriesContainer');
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
        authorEntriesContainer.appendChild(newEntry);
    });

    // Remove author entry
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('remove-author-entry') || e.target.closest('.remove-author-entry'))) {
            const authorEntriesContainer = document.getElementById('authorEntriesContainer');
            if (authorEntriesContainer.children.length > 1) {
                e.target.closest('.author-entry').remove();
            } else {
                alert('At least one author entry is required.');
            }
        }
    });

    // Replace the single author save with multiple authors save
    document.getElementById('saveAuthors').addEventListener('click', function() {
        const authorEntries = document.querySelectorAll('.author-entry');
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
            alert('First name and last name are required for all authors.');
            return;
        }
        
        if (authorsData.length === 0) {
            alert('Please add at least one author.');
            return;
        }
        
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
                        
                        // Close the modal
                        $('#addAuthorModal').modal('hide');
                        
                        // Clear the form
                        document.getElementById('newAuthorForm').reset();
                        // Reset to just one author entry
                        document.getElementById('authorEntriesContainer').innerHTML = `
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
                        `;
                        
                        alert(`Successfully added ${response.authors.length} author(s)!`);
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (e) {
                    alert('Error processing response: ' + e.message);
                }
            } else {
                alert('Error adding authors');
            }
        };
        xhr.send(JSON.stringify(authorsData));
    });

    // Initialize dropdown filters
    function filterDropdown(inputId, selectId) {
        const input = document.getElementById(inputId);
        const select = document.querySelector(selectId);
        input.addEventListener("keyup", function() {
            const filter = input.value.toLowerCase();
            const options = select.options;
            for (let i = 0; i < options.length; i++) {
                const optionText = options[i].text.toLowerCase();
                options[i].style.display = optionText.includes(filter) ? "" : "none";
            }
        });
    }

    function updatePreview(selectId, previewId) {
        const select = document.getElementById(selectId);
        const preview = document.getElementById(previewId);
        const selectedOptions = Array.from(select.selectedOptions).map(option => {
            return `<span class="badge bg-secondary mr-1 text-white">${option.text} <i class="fas fa-times remove-icon" data-value="${option.value}"></i></span>`;
        });
        preview.innerHTML = selectedOptions.join(' ');
    }

    function removeSelectedOption(selectId, previewId) {
        const preview = document.getElementById(previewId);
        preview.addEventListener("click", function(event) {
            if (event.target.classList.contains("remove-icon")) {
                const value = event.target.getAttribute("data-value");
                const select = document.getElementById(selectId);
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === value) {
                        select.options[i].selected = false;
                        break;
                    }
                }
                updatePreview(selectId, previewId);
            }
        });
    }

    // Update publisher search functionality to use existing search field
    function addPublisherSearch() {
        const publisherSelect = document.querySelector('select[name="publisher"]');
        const searchInput = document.getElementById('publisherSearch');
        
        if (!searchInput || !publisherSelect) return;
        
        // Store original options
        const originalOptions = Array.from(publisherSelect.options);
        
        // Add search functionality
        searchInput.addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            
            // Clear current options
            publisherSelect.innerHTML = '';
            
            // Add default "Select Publisher" option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select Publisher';
            publisherSelect.appendChild(defaultOption);
            
            // Filter and add matching options
            originalOptions.forEach(option => {
                if (option.value !== '' && option.text.toLowerCase().includes(searchText)) {
                    publisherSelect.appendChild(option.cloneNode(true));
                }
            });
        });
    }

    // Initialize dropdowns and selects
    filterDropdown("authorSearch", "select[name='author[]']");
    filterDropdown("coAuthorsSearch", "select[name='co_authors[]']");
    filterDropdown("editorsSearch", "select[name='editors[]']");

    document.getElementById("authorSelect").addEventListener("change", function() {
        updatePreview("authorSelect", "authorPreview");
    });
    document.getElementById("coAuthorsSelect").addEventListener("change", function() {
        updatePreview("coAuthorsSelect", "coAuthorsPreview");
    });
    document.getElementById("editorsSelect").addEventListener("change", function() {
        updatePreview("editorsSelect", "editorsPreview");
    });

    removeSelectedOption("authorSelect", "authorPreview");
    removeSelectedOption("coAuthorsSelect", "coAuthorsPreview");
    removeSelectedOption("editorsSelect", "editorsPreview");
    
    // Initialize publisher search
    addPublisherSearch();

    // Add publisher entry functionality
    document.getElementById('addPublisherEntry').addEventListener('click', function() {
        const publisherEntriesContainer = document.getElementById('publisherEntriesContainer');
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
        publisherEntriesContainer.appendChild(newEntry);
    });

    // Remove publisher entry
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('remove-publisher-entry') || e.target.closest('.remove-publisher-entry'))) {
            const publisherEntriesContainer = document.getElementById('publisherEntriesContainer');
            if (publisherEntriesContainer.children.length > 1) {
                e.target.closest('.publisher-entry').remove();
            } else {
                alert('At least one publisher entry is required.');
            }
        }
    });

    // Save publishers functionality
    document.getElementById('savePublishers').addEventListener('click', function() {
        const publisherEntries = document.querySelectorAll('.publisher-entry');
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
            alert('Publisher name and place are required for all publishers.');
            return;
        }
        
        if (publishersData.length === 0) {
            alert('Please add at least one publisher.');
            return;
        }
        
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
                        
                        // Close the modal
                        $('#addPublisherModal').modal('hide');
                        
                        // Clear the form
                        document.getElementById('newPublisherForm').reset();
                        // Reset to just one publisher entry
                        document.getElementById('publisherEntriesContainer').innerHTML = `
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
                        `;
                        
                        alert(`Successfully added ${response.publishers.length} publisher(s)!`);
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (e) {
                    alert('Error processing response: ' + e.message);
                }
            } else {
                alert('Error adding publishers');
            }
        };
        xhr.send(JSON.stringify(publishersData));
    });
});
