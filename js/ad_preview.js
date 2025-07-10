/**
 * Ad Preview JavaScript
 * Handles live preview functionality for the ad management interface
 */

document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.querySelector('input[name="image"]');
    const previewContainer = document.getElementById('adPreview');
    const titleInput = document.querySelector('input[name="title"]');
    const linkInput = document.querySelector('input[name="link_url"]');
    const ctaInput = document.querySelector('input[name="cta_text"]');
    const zoneSelect = document.querySelector('select[name="zone"]');

    // Handle image preview
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    updatePreview(e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Handle live preview updates
    if (titleInput) {
        titleInput.addEventListener('input', updatePreviewFromForm);
    }
    if (linkInput) {
        linkInput.addEventListener('input', updatePreviewFromForm);
    }
    if (ctaInput) {
        ctaInput.addEventListener('input', updatePreviewFromForm);
    }
    if (zoneSelect) {
        zoneSelect.addEventListener('change', updatePreviewFromForm);
    }

    // Update preview with image
    function updatePreview(imageSrc) {
        if (!previewContainer) return;

        const zone = zoneSelect ? zoneSelect.value : 'header';
        const title = titleInput ? titleInput.value : 'Ad Title';
        const link = linkInput ? linkInput.value : '#';
        const cta = ctaInput ? ctaInput.value : '';

        const previewHTML = generatePreviewHTML(imageSrc, title, link, cta, zone);
        previewContainer.innerHTML = previewHTML;
        previewContainer.classList.add('has-preview');
    }

    // Update preview from form data
    function updatePreviewFromForm() {
        const file = imageInput ? imageInput.files[0] : null;
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                updatePreview(e.target.result);
            };
            reader.readAsDataURL(file);
        } else {
            // Show placeholder if no image
            updatePreviewPlaceholder();
        }
    }

    // Generate preview HTML
    function generatePreviewHTML(imageSrc, title, link, cta, zone) {
        const zoneClasses = {
            'header': 'ad-header',
            'sidebar': 'ad-sidebar',
            'footer': 'ad-footer',
            'carousel': 'ad-carousel',
            'inline': 'ad-inline'
        };

        const zoneClass = zoneClasses[zone] || 'ad-generic';
        const containerClass = `ad-container ${zoneClass}`;

        let html = `<div class="${containerClass}">`;
        html += '<span class="ad-label">Advertisement</span>';
        html += `<a href="${link}" target="_blank" class="ad-link">`;
        html += `<img src="${imageSrc}" alt="${title}" class="ad-preview">`;
        
        if (cta) {
            html += `<div class="ad-cta">${cta}</div>`;
        }
        
        html += '</a></div>';

        return html;
    }

    // Update preview placeholder
    function updatePreviewPlaceholder() {
        if (!previewContainer) return;

        const zone = zoneSelect ? zoneSelect.value : '';
        let placeholderText = 'Upload an image to see the preview';
        
        if (zone) {
            placeholderText = `Upload an image to see the ${zone} preview`;
        }

        previewContainer.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-image fa-3x mb-3"></i>
                <p>${placeholderText}</p>
            </div>
        `;
        previewContainer.classList.remove('has-preview');
    }

    // Zone information highlighting
    const zoneItems = document.querySelectorAll('.zone-item');
    if (zoneSelect) {
        zoneSelect.addEventListener('change', function() {
            const selectedZone = this.value;
            
            // Remove active class from all zone items
            zoneItems.forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to selected zone
            if (selectedZone) {
                const activeItem = document.querySelector(`[data-zone="${selectedZone}"]`);
                if (activeItem) {
                    activeItem.classList.add('active');
                }
            }
        });
    }

    // Form validation enhancements
    const form = document.getElementById('adForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            // Validate URL format
            if (linkInput && linkInput.value) {
                const urlPattern = /^https?:\/\/.+/;
                if (!urlPattern.test(linkInput.value)) {
                    isValid = false;
                    linkInput.classList.add('is-invalid');
                }
            }

            // Validate date range
            const startDate = document.querySelector('input[name="start_date"]');
            const endDate = document.querySelector('input[name="end_date"]');
            
            if (startDate && endDate && startDate.value && endDate.value) {
                if (new Date(startDate.value) >= new Date(endDate.value)) {
                    isValid = false;
                    startDate.classList.add('is-invalid');
                    endDate.classList.add('is-invalid');
                }
            }

            if (!isValid) {
                e.preventDefault();
                showValidationMessage('Please fix the errors before submitting.');
            }
        });
    }

    // Show validation message
    function showValidationMessage(message) {
        // Remove existing validation message
        const existingMessage = document.querySelector('.validation-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        // Create new validation message
        const messageDiv = document.createElement('div');
        messageDiv.className = 'alert alert-danger validation-message';
        messageDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
        
        // Insert at the top of the form
        const form = document.getElementById('adForm');
        if (form) {
            form.parentNode.insertBefore(messageDiv, form);
            
            // Scroll to message
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Auto-save draft functionality (optional)
    let autoSaveTimer;
    const formInputs = document.querySelectorAll('#adForm input, #adForm select, #adForm textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(saveDraft, 2000); // Save after 2 seconds of inactivity
        });
    });

    function saveDraft() {
        const formData = new FormData(document.getElementById('adForm'));
        const draftData = {};
        
        for (let [key, value] of formData.entries()) {
            draftData[key] = value;
        }
        
        localStorage.setItem('adDraft', JSON.stringify(draftData));
        console.log('Draft saved automatically');
    }

    // Load draft on page load
    const savedDraft = localStorage.getItem('adDraft');
    if (savedDraft && !document.querySelector('input[name="title"]').value) {
        try {
            const draftData = JSON.parse(savedDraft);
            
            // Populate form fields
            Object.keys(draftData).forEach(key => {
                const field = document.querySelector(`[name="${key}"]`);
                if (field && draftData[key]) {
                    field.value = draftData[key];
                }
            });
            
            console.log('Draft loaded');
        } catch (e) {
            console.error('Error loading draft:', e);
        }
    }

    // Clear draft on successful submission
    if (form) {
        form.addEventListener('submit', function() {
            localStorage.removeItem('adDraft');
        });
    }
}); 