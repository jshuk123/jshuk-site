function confirmDelete(businessId) {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const deleteBusinessIdInput = document.getElementById('deleteBusinessId');
    
    if (deleteBusinessIdInput) {
        deleteBusinessIdInput.value = businessId;
    }
    
    if (deleteModal) {
        deleteModal.show();
    }
}
