<div class="modal fade" tabindex="-1" id = "confirmBulkOperation">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Confirm that you want to proceed</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" wire:click = "$dispatch('dispatchBulkMethodName')" data-bs-dismiss="modal">Yes, continue!</button>
            </div>
        </div>
    </div>
</div>