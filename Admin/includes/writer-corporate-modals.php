<!-- Add Writer Modal -->
<div class="modal fade" id="addWriterModal" tabindex="-1" aria-labelledby="addWriterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addWriterModalLabel">Add New Writer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="writerForm">
                    <div class="mb-3">
                        <label for="writerFirstname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="writerFirstname" required>
                    </div>
                    <div class="mb-3">
                        <label for="writerMiddleInit" class="form-label">Middle Initial</label>
                        <input type="text" class="form-control" id="writerMiddleInit">
                    </div>
                    <div class="mb-3">
                        <label for="writerLastname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="writerLastname" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveWriterBtn">Save Writer</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Corporate Modal -->
<div class="modal fade" id="addCorporateModal" tabindex="-1" aria-labelledby="addCorporateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCorporateModalLabel">Add New Corporate Entity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="corporateForm">
                    <div class="mb-3">
                        <label for="corporateName" class="form-label">Corporate Name</label>
                        <input type="text" class="form-control" id="corporateName" required>
                    </div>
                    <div class="mb-3">
                        <label for="corporateType" class="form-label">Type</label>
                        <select class="form-control" id="corporateType" required>
                            <option value="">Select Type</option>
                            <option value="Government Institution">Government Institution</option>
                            <option value="University">University</option>
                            <option value="Research Institute">Research Institute</option>
                            <option value="Corporation">Corporation</option>
                            <option value="Non-Profit Organization">Non-Profit Organization</option>
                            <option value="University Press">University Press</option>
                            <option value="Professional Association">Professional Association</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="corporateLocation" class="form-label">Location</label>
                        <input type="text" class="form-control" id="corporateLocation">
                    </div>
                    <div class="mb-3">
                        <label for="corporateDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="corporateDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCorporateBtn">Save Corporate Entity</button>
            </div>
        </div>
    </div>
</div>
