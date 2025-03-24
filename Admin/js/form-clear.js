$(document).ready(function() {
    // Handle clear tab button clicks
    $('.clear-tab-btn').on('click', function() {
        const tabId = $(this).data('tab-id');
        const tabPane = $('#' + tabId);
        
        // Confirm before clearing
        if (confirm('Are you sure you want to clear all fields in this tab?')) {
            // Clear all input types within this tab
            tabPane.find('input[type="text"], input[type="number"], input[type="url"], input[type="email"]').val('');
            tabPane.find('textarea').val('');
            tabPane.find('select').prop('selectedIndex', 0);
            tabPane.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
            
            // Handle special cases like file inputs
            tabPane.find('input[type="file"]').val('');
            tabPane.find('.custom-file-label').text('Choose file');
            
            // Clear any dynamically generated content based on tab type
            if (tabId === 'subject-entry') {
                // Keep first subject entry but clear its fields
                $('#subject-entries').find('.subject-entry:not(:first)').remove();
                $('#subject-entries .subject-entry:first select').prop('selectedIndex', 0);
                $('#subject-entries .subject-entry:first textarea').val('');
            }
            
            if (tabId === 'local-info') {
                // Keep first accession group but reset its values
                $('#accessionContainer .accession-group:not(:first)').remove();
                $('#accessionContainer .accession-group:first input.accession-input').val('');
                $('#accessionContainer .accession-group:first input.copies-input').val('1');
                
                // Clear call numbers
                $('#callNumberContainer').empty();
            }
            
            if (tabId === 'publication') {
                // Clear author selections
                $('#authorPreview, #coAuthorsPreview, #editorsPreview').empty();
                $('#authorSelect, #coAuthorsSelect, #editorsSelect').val('');
                
                // Clear ISBN container if it has dynamic content
                if ($('#isbnContainer').children().length > 0) {
                    $('#isbnContainer').empty();
                }
            }
            
            // Mark the tab as incomplete in the navigation
            $('#' + tabId + '-tab').removeClass('completed');
            
            // Update form progress if function exists
            if (typeof updateFormProgress === 'function') {
                updateFormProgress();
            }
            
            // Show success message
            toastr.success('Tab fields have been cleared');
        }
    });

    // Clear Form Button Click Handler
    $('.btn-warning[data-clear-form]').on('click', function() {
        if (confirm('Are you sure you want to clear all form data?')) {
            // Clear localStorage
            localStorage.removeItem('bookFormData');

            const form = document.getElementById('bookForm');

            // Clear all basic form elements
            form.querySelectorAll('input:not([readonly]), textarea, select').forEach(field => {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = false;
                } else if (field.type === 'file') {
                    field.value = '';
                    // Reset file input labels
                    const label = field.nextElementSibling;
                    if (label && label.classList.contains('custom-file-label')) {
                        const originalLabel = field.id === 'front_image' ? 'Front Cover' : 'Back Cover';
                        label.textContent = originalLabel;
                    }
                } else if (field.tagName === 'SELECT' && field.multiple) {
                    $(field).val(null).trigger('change');
                    // Clear preview if exists
                    const previewId = field.id + 'Preview';
                    const preview = document.getElementById(previewId);
                    if (preview) preview.innerHTML = '';
                } else {
                    field.value = '';
                }
            });

            // Reset subject entries to single empty entry
            const subjectEntries = document.getElementById('subject-entries');
            if (subjectEntries) {
                while (subjectEntries.children.length > 1) {
                    subjectEntries.removeChild(subjectEntries.lastChild);
                }
                const firstEntry = subjectEntries.querySelector('.subject-entry');
                if (firstEntry) {
                    firstEntry.querySelectorAll('select, textarea').forEach(field => field.value = '');
                }
            }

            // Reset accession entries to single empty entry
            const accessionContainer = document.getElementById('accessionContainer');
            if (accessionContainer) {
                while (accessionContainer.children.length > 1) {
                    accessionContainer.removeChild(accessionContainer.lastChild);
                }
                const firstGroup = accessionContainer.querySelector('.accession-group');
                if (firstGroup) {
                    firstGroup.querySelectorAll('input:not([readonly])').forEach(field => {
                        field.value = field.name.includes('number_of_copies') ? '1' : '';
                    });
                }
            }

            // Reset progress bar
            const progressBar = document.getElementById('formProgressBar');
            if (progressBar) {
                progressBar.style.width = '0%';
                progressBar.setAttribute('aria-valuenow', '0');
            }

            // Reset all tabs status
            document.querySelectorAll('#formTabs .nav-link').forEach(tab => {
                tab.classList.remove('completed', 'active');
            });

            // Switch to first tab
            const firstTab = document.querySelector('#formTabs .nav-link:first-child');
            const firstTabPane = document.querySelector('#title-proper');
            if (firstTab && firstTabPane) {
                // Remove active class from all tab panes
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('active', 'show');
                });
                
                // Activate first tab
                firstTab.classList.add('active');
                firstTabPane.classList.add('active', 'show');
            }

            // Show success message
            if (typeof toastr !== 'undefined') {
                toastr.success('Form has been cleared successfully');
            } else {
                alert('Form has been cleared successfully');
            }
        }
    });
});