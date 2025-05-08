/**
 * Contributor Select Component
 * Provides functionality for selecting contributors with roles
 */

class ContributorSelect {
  constructor(options) {
    this.options = Object.assign({
      // Default options
      containerId: 'contributorSelectContainer',
      writersData: [],
      selectedContributors: [],
      roles: {
        'author': 'Main Author',
        'co_author': 'Co-Author',
        'editor': 'Editor'
      },
      onSelectionChange: null,
      addNewCallback: null
    }, options);
    
    this.init();
  }
  
  init() {
    this.container = document.getElementById(this.options.containerId);
    if (!this.container) {
      console.error(`Container with ID "${this.options.containerId}" not found`);
      return;
    }
    
    this.render();
    this.setupEventListeners();
    this.updateCounter();
  }
  
  render() {
    // Create the component structure
    this.container.innerHTML = `
      <div class="contributor-select-container">
        <div class="contributor-select-header">
          <h6 class="m-0">Contributors</h6>
          <span class="badge badge-pill badge-primary">
            <span id="contributorCount">0</span> Selected
          </span>
        </div>
        
        <div class="contributor-select-search">
          <input type="text" class="form-control" id="contributorSearch" 
                 placeholder="Search contributors...">
          <i class="fas fa-search search-icon"></i>
        </div>
        
        <button type="button" class="btn btn-outline-primary btn-sm contributor-select-add-btn" id="addContributorBtn">
          <i class="fas fa-plus-circle mr-1"></i> Add New Contributor
        </button>
        
        <div class="contributor-select-items" id="contributorItems">
          ${this.renderItems()}
        </div>
        
        <!-- Hidden inputs to store selected contributors and roles -->
        <div id="contributorHiddenInputs"></div>
      </div>
    `;
    
    // Reference key elements
    this.searchInput = this.container.querySelector('#contributorSearch');
    this.itemsContainer = this.container.querySelector('#contributorItems');
    this.countDisplay = this.container.querySelector('#contributorCount');
    this.hiddenInputs = this.container.querySelector('#contributorHiddenInputs');
  }
  
  renderItems() {
    if (!this.options.selectedContributors || this.options.selectedContributors.length === 0) {
      return `
        <div class="contributor-empty-state">
          <i class="fas fa-users"></i>
          <p>No contributors selected yet</p>
        </div>
      `;
    }
    
    let html = '';
    this.options.selectedContributors.forEach((contributor, index) => {
      html += this.renderContributorItem(contributor, index);
    });
    
    return html;
  }
  
  renderContributorItem(contributor, index) {
    const roleOptions = Object.entries(this.options.roles)
      .map(([value, label]) => {
        const selected = contributor.role === value ? 'selected' : '';
        return `<option value="${value}" ${selected}>${label}</option>`;
      })
      .join('');
      
    return `
      <div class="contributor-item" data-index="${index}" data-id="${contributor.id}">
        <div class="contributor-item-name">
          ${contributor.name}
        </div>
        <div class="contributor-item-role">
          <select class="form-control form-control-sm contributor-role-select" data-index="${index}">
            ${roleOptions}
          </select>
        </div>
        <div class="contributor-actions">
          <button type="button" class="btn btn-danger btn-sm contributor-action-btn remove-contributor" data-index="${index}">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    `;
  }
  
  setupEventListeners() {
    // Search functionality
    this.searchInput.addEventListener('input', () => {
      this.filterWritersList();
    });
    
    // Add new contributor button
    const addBtn = this.container.querySelector('#addContributorBtn');
    addBtn.addEventListener('click', () => {
      this.showContributorSelectionModal();
    });
    
    // Event delegation for item actions
    this.itemsContainer.addEventListener('click', (e) => {
      const removeBtn = e.target.closest('.remove-contributor');
      if (removeBtn) {
        const index = parseInt(removeBtn.dataset.index, 10);
        this.removeContributor(index);
      }
    });
    
    // Role change event
    this.itemsContainer.addEventListener('change', (e) => {
      if (e.target.classList.contains('contributor-role-select')) {
        const index = parseInt(e.target.dataset.index, 10);
        this.updateContributorRole(index, e.target.value);
      }
    });
  }
  
  showContributorSelectionModal() {
    // Create a modal to select contributors from the list
    const modalId = 'contributorSelectModal';
    let modalElement = document.getElementById(modalId);
    
    if (!modalElement) {
      modalElement = document.createElement('div');
      modalElement.id = modalId;
      modalElement.className = 'modal fade contributor-select-modal';
      modalElement.tabIndex = -1;
      modalElement.setAttribute('aria-labelledby', `${modalId}Label`);
      modalElement.setAttribute('aria-hidden', true);
      
      document.body.appendChild(modalElement);
    }

    // Show loading state in modal
    modalElement.innerHTML = `
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="${modalId}Label">Select Contributors</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading contributors...</p>
          </div>
        </div>
      </div>
    `;
    
    // Show the modal with loading state
    $(modalElement).modal('show');

    // Determine which API endpoint to use based on whether this is an individual or corporate contributor selector
    let apiEndpoint = 'ajax/get_writers.php'; // Default for individual contributors
    
    // Check if this is a corporate contributor component by checking the roles
    const hasCorporateRoles = Object.keys(this.options.roles).some(role => 
      ['corporate_author', 'corporate_contributor', 'publisher', 'distributor', 
       'sponsor', 'funding_body', 'research_institution'].includes(role)
    );
    
    if (hasCorporateRoles) {
      apiEndpoint = 'ajax/get_corporates.php';
    }

    // Fetch fresh data from the server
    fetch(apiEndpoint)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        // Update the component's data with fresh data from the server
        this.refreshWritersData(
          apiEndpoint === 'ajax/get_corporates.php' 
            ? data.map(corporate => ({
                id: corporate.id,
                name: `${corporate.name} (${corporate.type})`
              }))
            : data.writers || data
        );
        
        // Collect IDs of already selected contributors
        const selectedIds = this.options.selectedContributors.map(c => c.id);
        
        // Generate list of available contributors with the fresh data
        const writersList = this.options.writersData
          .map(writer => {
            const isSelected = selectedIds.includes(writer.id);
            const selectedClass = isSelected ? 'selected' : '';
            const disabledAttr = isSelected ? 'disabled' : '';
            
            return `
              <div class="contributor-select-item ${selectedClass}" 
                   data-id="${writer.id}" 
                   data-name="${writer.name}" 
                   ${disabledAttr}>
                <div class="d-flex justify-content-between">
                  <span>${writer.name}</span>
                  ${isSelected ? '<i class="fas fa-check text-success"></i>' : ''}
                </div>
              </div>
            `;
          })
          .join('');
        
        // Generate modal HTML with the fresh data
        modalElement.innerHTML = `
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="${modalId}Label">Select Contributors</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <input type="text" class="form-control mb-3" id="modalContributorSearch" 
                         placeholder="Search contributors...">
                </div>
                <div class="contributor-select-list">
                  ${writersList}
                </div>
                <div class="text-center mt-3">
                  <button type="button" class="btn btn-secondary btn-sm" id="createNewContributorBtn">
                    <i class="fas fa-plus"></i> Create New Contributor
                  </button>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="cancelContributorSelection">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmContributorSelection">
                  Add Selected
                </button>
              </div>
            </div>
          </div>
        `;
        
        // Setup modal event listeners
        modalElement.querySelector('.close').addEventListener('click', () => {
          $(modalElement).modal('hide');
        });
        
        modalElement.querySelector('#cancelContributorSelection').addEventListener('click', () => {
          $(modalElement).modal('hide');
        });
        
        modalElement.querySelector('#modalContributorSearch').addEventListener('input', (e) => {
          const searchTerm = e.target.value.toLowerCase();
          modalElement.querySelectorAll('.contributor-select-item').forEach(item => {
            const name = item.dataset.name.toLowerCase();
            if (name.includes(searchTerm)) {
              item.style.display = '';
            } else {
              item.style.display = 'none';
            }
          });
        });
        
        // Handle contributor selection
        modalElement.querySelectorAll('.contributor-select-item:not([disabled])').forEach(item => {
          item.addEventListener('click', () => {
            item.classList.toggle('selected');
          });
        });
        
        // Handle create new contributor button
        modalElement.querySelector('#createNewContributorBtn').addEventListener('click', () => {
          if (this.options.addNewCallback && typeof this.options.addNewCallback === 'function') {
            // Close the current modal
            $(modalElement).modal('hide');
            
            // Call the add new contributor function
            this.options.addNewCallback();
          }
        });
        
        // Handle confirm button
        modalElement.querySelector('#confirmContributorSelection').addEventListener('click', () => {
          const selectedItems = modalElement.querySelectorAll('.contributor-select-item.selected:not([disabled])');
          
          selectedItems.forEach(item => {
            const id = parseInt(item.dataset.id, 10);
            const name = item.dataset.name;
            
            // Add as a new contributor with default role
            this.addContributor({
              id: id,
              name: name,
              role: Object.keys(this.options.roles)[0] || 'author' // Use first available role as default
            });
          });
          
          $(modalElement).modal('hide');
        });
      })
      .catch(error => {
        console.error('Error fetching contributors data:', error);
        modalElement.querySelector('.modal-body').innerHTML = `
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> 
            Error loading contributors. Please try again.
          </div>
        `;
      });

    // Cleanup when modal is hidden
    $(modalElement).on('hidden.bs.modal', function () {
      $(this).remove();
    });
  }
  
  filterWritersList() {
    const searchTerm = this.searchInput.value.toLowerCase();
    
    // Filter the currently selected contributors in the main component
    const contributorItems = this.itemsContainer.querySelectorAll('.contributor-item');
    
    if (contributorItems.length === 0) return; // No items to filter
    
    contributorItems.forEach(item => {
      const contributorName = item.querySelector('.contributor-item-name').textContent.trim().toLowerCase();
      
      // Show/hide based on search match
      if (contributorName.includes(searchTerm)) {
        item.style.display = '';
      } else {
        item.style.display = 'none';
      }
    });
    
    // If there are no visible items after filtering and there's a search term, show a message
    const hasVisibleItems = Array.from(contributorItems).some(item => item.style.display !== 'none');
    const emptyState = this.itemsContainer.querySelector('.contributor-empty-state');
    
    if (!hasVisibleItems && searchTerm && !emptyState) {
      // Create a temporary "no results" message
      const noResults = document.createElement('div');
      noResults.className = 'contributor-empty-state no-results';
      noResults.innerHTML = `
        <i class="fas fa-search"></i>
        <p>No contributors match "${searchTerm}"</p>
      `;
      this.itemsContainer.appendChild(noResults);
    } else if ((hasVisibleItems || !searchTerm) && this.itemsContainer.querySelector('.no-results')) {
      // Remove the "no results" message if items are visible or search is cleared
      this.itemsContainer.querySelector('.no-results').remove();
    }
  }
  
  addContributor(contributor) {
    // Check if contributor already exists
    const exists = this.options.selectedContributors.some(c => c.id === contributor.id);
    if (exists) return;
    
    // Add to the list
    this.options.selectedContributors.push(contributor);
    
    // Check if empty state needs to be removed
    if (this.itemsContainer.querySelector('.contributor-empty-state')) {
      this.itemsContainer.innerHTML = '';
    }
    
    // Create and append the new item
    const newItem = document.createElement('div');
    newItem.innerHTML = this.renderContributorItem(contributor, this.options.selectedContributors.length - 1);
    this.itemsContainer.appendChild(newItem.firstElementChild);
    
    // Update hidden inputs and counter
    this.updateHiddenInputs();
    this.updateCounter();
    
    // Call the change callback if provided
    if (this.options.onSelectionChange && typeof this.options.onSelectionChange === 'function') {
      this.options.onSelectionChange(this.options.selectedContributors);
    }
  }
  
  removeContributor(index) {
    if (index < 0 || index >= this.options.selectedContributors.length) return;
    
    // Remove from array
    this.options.selectedContributors.splice(index, 1);
    
    // Re-render all items to ensure correct indices
    this.itemsContainer.innerHTML = this.renderItems();
    
    // Update hidden inputs and counter
    this.updateHiddenInputs();
    this.updateCounter();
    
    // Call the change callback if provided
    if (this.options.onSelectionChange && typeof this.options.onSelectionChange === 'function') {
      this.options.onSelectionChange(this.options.selectedContributors);
    }
  }
  
  updateContributorRole(index, role) {
    if (index < 0 || index >= this.options.selectedContributors.length) return;
    
    // Update the role
    this.options.selectedContributors[index].role = role;
    
    // Update hidden inputs
    this.updateHiddenInputs();
    
    // Call the change callback if provided
    if (this.options.onSelectionChange && typeof this.options.onSelectionChange === 'function') {
      this.options.onSelectionChange(this.options.selectedContributors);
    }
  }
  
  updateCounter() {
    this.countDisplay.textContent = this.options.selectedContributors.length;
  }
  
  updateHiddenInputs() {
    // Clear existing inputs
    this.hiddenInputs.innerHTML = '';
    
    // Create hidden inputs for selected contributors
    this.options.selectedContributors.forEach((contributor, index) => {
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'contributor_ids[]';
      idInput.value = contributor.id;
      
      const roleInput = document.createElement('input');
      roleInput.type = 'hidden';
      roleInput.name = 'contributor_roles[]';
      roleInput.value = contributor.role;
      
      this.hiddenInputs.appendChild(idInput);
      this.hiddenInputs.appendChild(roleInput);
    });
  }
  
  // Public API methods
  getSelectedContributors() {
    return [...this.options.selectedContributors];
  }
  
  setSelectedContributors(contributors) {
    this.options.selectedContributors = contributors;
    this.itemsContainer.innerHTML = this.renderItems();
    this.updateHiddenInputs();
    this.updateCounter();
  }
  
  refreshWritersData(newData) {
    this.options.writersData = newData;
  }
}

// Make the class available globally
window.ContributorSelect = ContributorSelect;
