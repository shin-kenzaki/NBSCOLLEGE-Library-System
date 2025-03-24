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
});