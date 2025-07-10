// Preview profile image before upload
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profileImagePreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Trigger file input when clicking on image container
document.querySelector('.profile-image-container').addEventListener('click', function() {
    document.getElementById('profile_image').click();
}); 