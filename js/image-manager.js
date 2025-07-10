class ImageManager {
    constructor(options) {
        this.businessId = options.businessId;
        this.apiEndpoint = '/users/image_manager.php';  // Always use this endpoint
        this.mainImageContainer = options.mainImageContainer;
        this.galleryContainer = options.galleryContainer;
        this.mainImageInput = options.mainImageInput;
        this.galleryImageInput = options.galleryImageInput;
        this.onUpdate = options.onUpdate || (() => {});
        
        // Initialize editor and cropper containers
        this.initializeEditorContainers();
        this.initializeEventListeners();
        this.loadImages();

        // Check dependencies
        this.checkDependencies();
    }

    checkDependencies() {
        // Check if Cropper.js is available
        if (typeof Cropper === 'undefined') {
            console.error('Cropper.js is not loaded. Loading it now...');
            this.loadCropperJS();
        } else {
            console.log('Cropper.js is loaded successfully');
        }

        // Check if TUI Image Editor is available
        if (typeof tui === 'undefined') {
            console.error('TUI Image Editor is not loaded. Loading it now...');
            this.loadTuiEditor();
        } else {
            console.log('TUI Image Editor is loaded successfully');
        }
    }

    loadCropperJS() {
        // Load Cropper.js CSS if not already loaded
        if (!document.querySelector('link[href*="cropper.min.css"]')) {
            const cssLink = document.createElement('link');
            cssLink.rel = 'stylesheet';
            cssLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css';
            document.head.appendChild(cssLink);
        }

        // Load Cropper.js script
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js';
        script.onload = () => {
            console.log('Cropper.js loaded successfully');
        };
        script.onerror = () => {
            console.error('Failed to load Cropper.js');
            this.showError('Failed to load image cropper library. Please refresh the page and try again.');
        };
        document.head.appendChild(script);
    }

    loadTuiEditor() {
        const dependencies = [
            {
                type: 'css',
                url: 'https://uicdn.toast.com/tui-color-picker/latest/tui-color-picker.min.css'
            },
            {
                type: 'css',
                url: 'https://uicdn.toast.com/tui-image-editor/latest/tui-image-editor.css'
            },
            {
                type: 'script',
                url: 'https://uicdn.toast.com/tui.code-snippet/latest/tui-code-snippet.min.js'
            },
            {
                type: 'script',
                url: 'https://uicdn.toast.com/tui-color-picker/latest/tui-color-picker.min.js'
            },
            {
                type: 'script',
                url: 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/4.4.0/fabric.min.js'
            },
            {
                type: 'script',
                url: 'https://uicdn.toast.com/tui-image-editor/latest/tui-image-editor.min.js'
            }
        ];

        dependencies.forEach(dep => {
            if (dep.type === 'css' && !document.querySelector(`link[href="${dep.url}"]`)) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = dep.url;
                document.head.appendChild(link);
            } else if (dep.type === 'script' && !document.querySelector(`script[src="${dep.url}"]`)) {
                const script = document.createElement('script');
                script.src = dep.url;
                document.head.appendChild(script);
            }
        });
    }

    initializeEditorContainers() {
        console.log('Initializing editor containers');
        // Remove existing modals if they exist
        const existingCropperModal = document.getElementById('imageCropperModal');
        if (existingCropperModal) {
            existingCropperModal.remove();
        }

        // Create modal containers for editor and cropper
        const modalHtml = `
            <!-- Image Editor Modal -->
            <div class="modal fade" id="imageEditorModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div id="tuiImageEditor"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveEditedImage">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Image Cropper Modal -->
            <div class="modal fade" id="imageCropperModal" tabindex="-1" role="dialog" aria-labelledby="cropperModalLabel">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cropperModalLabel">Crop Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="img-container">
                                <img id="cropperImage" src="" alt="Image to crop">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="crop-controls">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="saveCroppedImage">
                                    <span class="button-text">Save Changes</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modals to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        console.log('Modal HTML added to body');

        // Initialize the editor and cropper instances
        this.initializeImageEditor();
        this.initializeCropper();
    }

    initializeImageEditor() {
        this.editorModal = new bootstrap.Modal(document.getElementById('imageEditorModal'));
        this.editorInstance = null;
        this.currentEditingImageId = null;

        document.getElementById('saveEditedImage').addEventListener('click', () => this.saveEditedImage());
    }

    initializeCropper() {
        console.log('Initializing cropper');
        const cropperModal = document.getElementById('imageCropperModal');
        if (!cropperModal) {
            console.error('Cropper modal not found after initialization');
            return;
        }

        this.cropperModal = new bootstrap.Modal(cropperModal);
        this.cropperInstance = null;
        this.currentCroppingImageId = null;

        // Add event listener for modal close
        cropperModal.addEventListener('hidden.bs.modal', () => {
            console.log('Modal hidden, cleaning up');
            if (this.cropperInstance) {
                this.cropperInstance.destroy();
                this.cropperInstance = null;
            }
            this.currentCroppingImageId = null;
        });

        // Add event listener for save button
        const saveButton = document.getElementById('saveCroppedImage');
        if (saveButton) {
            saveButton.addEventListener('click', () => {
                console.log('Save button clicked');
                this.saveCroppedImage();
            });
        } else {
            console.error('Save button not found');
        }
    }

    initializeEventListeners() {
        // Main image upload
        if (this.mainImageInput) {
            this.mainImageInput.addEventListener('change', (e) => this.handleMainImageUpload(e));
        }

        // Gallery image upload
        if (this.galleryImageInput) {
            this.galleryImageInput.addEventListener('change', (e) => this.handleGalleryImageUpload(e));
        }

        // Gallery container drag and drop
        if (this.galleryContainer) {
            this.initializeDragAndDrop();
        }
    }

    initializeDragAndDrop() {
        const sortable = new Sortable(this.galleryContainer, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: () => {
                const items = this.galleryContainer.querySelectorAll('.gallery-item');
                const orderData = Array.from(items).map((item, index) => ({
                    id: item.dataset.imageId,
                    order: index
                }));
                this.updateOrder(orderData);
            }
        });
    }

    async loadImages() {
        try {
            // Load main image
            const mainResponse = await this.apiRequest('get_main', {
                business_id: this.businessId
            });

            if (mainResponse.success && mainResponse.image) {
                this.displayMainImage(mainResponse.image);
            }

            // Load gallery images
            const galleryResponse = await this.apiRequest('get_gallery', {
                business_id: this.businessId
            });

            if (galleryResponse.success && galleryResponse.images) {
                this.displayGalleryImages(galleryResponse.images);
            }
        } catch (error) {
            console.error('Error loading images:', error);
            this.showError('Failed to load images');
        }
    }

    async handleMainImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (!this.validateFile(file)) {
            event.target.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('image', file);
        formData.append('business_id', this.businessId);
        formData.append('action', 'upload');
        formData.append('type', 'main');

        try {
            const response = await this.apiRequest('upload', formData);
            if (response.success) {
                // Create image data object in the format expected by displayMainImage
                const imageData = {
                    id: response.id,
                    file_path: response.file_path
                };
                this.displayMainImage(imageData);
                this.showSuccess('Main image updated successfully');
                this.onUpdate('main', imageData);
            } else {
                this.showError(response.message || 'Failed to upload main image');
            }
        } catch (error) {
            console.error('Error uploading main image:', error);
            this.showError('Failed to upload main image');
        }

        event.target.value = '';
    }

    async handleGalleryImageUpload(event) {
        const files = event.target.files;
        if (!files.length) return;

        const uploadPromises = Array.from(files).map(async (file) => {
            if (!this.validateFile(file)) return null;

            const formData = new FormData();
            formData.append('image', file);
            formData.append('business_id', this.businessId);
            formData.append('action', 'upload');
            formData.append('type', 'gallery');

            try {
                const response = await this.apiRequest('upload', formData);
                if (response.success) {
                    this.addGalleryImage(response);
                    this.onUpdate('gallery', response);
                    return response;
                } else {
                    // If the error is about the image limit, hide the uploader.
                    if (response.message && response.message.includes('upload limit')) {
                        const uploadButton = document.querySelector('button[onclick*="gallery_image_input"]');
                        if (uploadButton) {
                            uploadButton.style.display = 'none';
                            const upgradeMessage = document.createElement('p');
                            upgradeMessage.className = 'text-warning small mt-2';
                            upgradeMessage.innerHTML = 'You have reached your gallery limit. <a href="/payment/subscription.php">Upgrade your plan</a> to add more.';
                            uploadButton.parentElement.appendChild(upgradeMessage);
                        }
                    }
                    this.showError(response.message);
                    return null;
                }
            } catch (error) {
                console.error('Error uploading gallery image:', error);
                this.showError(`Failed to upload ${file.name}`);
                return null;
            }
        });

        await Promise.all(uploadPromises);
        event.target.value = '';
        this.showSuccess('Gallery images uploaded successfully');
    }

    async deleteImage(imageId, imageType) {
        try {
            const response = await this.apiRequest('delete', { image_id: imageId });

            if (response.success) {
                this.showSuccess('Image deleted successfully');
                
                if (imageType === 'main') {
                    this.displayMainImagePlaceholder();
                } else {
                    const galleryItem = this.galleryContainer.querySelector(`.gallery-item[data-image-id="${imageId}"]`);
                    if (galleryItem) {
                        galleryItem.remove();
                    }
                    // If gallery is now empty, show placeholder
                    if (this.galleryContainer.querySelectorAll('.gallery-item').length === 0) {
                        this.galleryContainer.innerHTML += `
                            <div class="gallery-placeholder">
                                <i class="fas fa-images"></i>
                                <p>No gallery images uploaded</p>
                            </div>
                        `;
                    }
                }
                
                this.onUpdate('delete', { imageId, type: imageType });
            } else {
                this.showError(response.message || 'Failed to delete image');
            }
        } catch (error) {
            console.error('Error deleting image:', error);
            this.showError('Failed to delete image');
        }
    }

    async setMainImage(imageId) {
        try {
            const response = await this.apiRequest('set_main', {
                business_id: this.businessId,
                image_id: imageId
            });

            if (response.success) {
                await this.loadImages(); // Reload all images to reflect changes
                this.showSuccess('Main image updated successfully');
                this.onUpdate('main_updated', { imageId });
            }
        } catch (error) {
            console.error('Error setting main image:', error);
            this.showError('Failed to set main image');
        }
    }

    async updateOrder(orderData) {
        try {
            const response = await this.apiRequest('update_order', {
                business_id: this.businessId,
                order_data: orderData
            });

            if (response.success) {
                this.showSuccess('Gallery order updated');
                this.onUpdate('order_updated', { orderData });
            }
        } catch (error) {
            console.error('Error updating order:', error);
            this.showError('Failed to update gallery order');
        }
    }

    displayMainImage(imageData) {
        if (!this.mainImageContainer) return;

        if (!imageData || !imageData.file_path) {
            this.displayMainImagePlaceholder();
            return;
        }

        const imageUrl = imageData.file_path;
        this.mainImageContainer.innerHTML = `
            <div class="main-image-wrapper">
                <img src="${imageUrl}?v=${new Date().getTime()}" alt="Main business image" class="main-image">
                <div class="image-options">
                    <div class="image-preview">
                        <button type="button" class="btn btn-info btn-sm view-business" onclick="window.open('/business.php?id=${this.businessId}', '_blank')">
                            <i class="fas fa-eye"></i> View Business
                        </button>
                    </div>
                    <div class="image-actions">
                        <button type="button" class="btn btn-secondary btn-sm edit-image" data-image-id="${imageData.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" class="btn btn-primary btn-sm crop-image" data-image-id="${imageData.id}">
                            <i class="fas fa-crop"></i> Crop
                        </button>
                        <button type="button" class="btn btn-danger btn-sm delete-image" data-image-id="${imageData.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Add event listeners
        const deleteBtn = this.mainImageContainer.querySelector('.delete-image');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => this.deleteImage(imageData.id, 'main'));
        }

        const editBtn = this.mainImageContainer.querySelector('.edit-image');
        if (editBtn) {
            editBtn.addEventListener('click', () => this.editImage(imageData.id));
        }

        const cropBtn = this.mainImageContainer.querySelector('.crop-image');
        if (cropBtn) {
            cropBtn.addEventListener('click', () => this.cropImage(imageData.id));
        }
    }

    displayMainImagePlaceholder() {
        if (!this.mainImageContainer) return;
        this.mainImageContainer.innerHTML = `
            <div class="main-image-placeholder">
                <i class="fas fa-image"></i>
                <p>No main image set</p>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('main_image_input').click();">
                    <i class="fas fa-upload"></i> Upload Main Image
                </button>
            </div>
        `;
    }

    async editImage(imageId) {
        try {
            console.log('Starting edit for image:', imageId);

            // Wait for TUI Image Editor to be loaded
            if (typeof tui === 'undefined' || !tui.ImageEditor) {
                console.log('TUI Image Editor not loaded, waiting...');
                await this.waitForTuiEditor();
            }
            
            const imageElement = this.findImageElement(imageId);
            if (!imageElement) {
                console.error('Image element not found');
                return;
            }

            this.currentEditingImageId = imageId;
            const imageUrl = imageElement.src + (imageElement.src.includes('?') ? '&' : '?') + 'timestamp=' + new Date().getTime();
            console.log('Loading image:', imageUrl);

            // Show loading state
            const loadingHtml = `
                <div class="editor-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading editor...
                </div>
            `;
            document.getElementById('tuiImageEditor').innerHTML = loadingHtml;

            // Show modal before initializing editor
            this.editorModal = new bootstrap.Modal(document.getElementById('imageEditorModal'));
            this.editorModal.show();

            // Initialize TUI Image Editor if not already initialized
            if (this.editorInstance) {
                this.editorInstance.destroy();
                this.editorInstance = null;
            }

            console.log('Initializing new editor instance');
            this.editorInstance = new tui.ImageEditor('#tuiImageEditor', {
                includeUI: {
                    loadImage: {
                        path: imageUrl,
                        name: 'Image'
                    },
                    theme: {
                        // A more professional and cohesive dark theme
                        'common.bi.image': '', // No background image
                        'common.backgroundColor': '#1e1e1e',
                        'common.border': '0px',
                        'header.backgroundImage': 'none',
                        'header.backgroundColor': 'transparent',
                        'header.border': '0px',
                        'menu.normalIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/svg/icon-d.svg',
                        'menu.activeIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/svg/icon-b.svg',
                        'menu.disabledIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/svg/icon-a.svg',
                        'menu.hoverIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/svg/icon-c.svg',
                        'menu.iconSize.width': '24px',
                        'menu.iconSize.height': '24px',
                        'submenu.normalIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/svg/icon-d.svg',
                        'submenu.activeIcon.path': 'https://uicdn.toast.com/tui-image-editor/latest/svg/icon-b.svg',
                        'submenu.iconSize.width': '32px',
                        'submenu.iconSize.height': '32px',
                        'submenu.backgroundColor': '#2e2e2e',
                        'submenu.partition.color': '#484848',
                        'submenu.normalLabel.color': '#e3e3e3',
                        'submenu.normalLabel.fontWeight': 'normal',
                        'submenu.activeLabel.color': '#fff',
                        'submenu.activeLabel.fontWeight': 'bold',
                        'checkbox.border': '1px solid #e3e3e3',
                        'checkbox.backgroundColor': '#fff',
                        'range.pointer.color': '#fff',
                        'range.bar.color': '#666',
                        'range.subbar.color': '#d1d1d1',
                        'range.value.color': '#fff',
                        'range.value.fontWeight': 'normal',
                        'range.value.fontSize': '11px',
                        'range.value.border': '1px solid #353535',
                        'range.value.backgroundColor': '#151515',
                        'range.title.color': '#fff',
                        'range.title.fontWeight': 'lighter',
                        'colorpicker.button.border': '1px solid #1e1e1e',
                        'colorpicker.title.color': '#fff'
                    },
                    menu: ['resize', 'crop', 'flip', 'rotate', 'draw', 'shape', 'icon', 'text', 'mask', 'filter'],
                    initMenu: 'filter',
                    uiSize: {
                        width: '100%',
                        height: '100%'
                    },
                    menuBarPosition: 'bottom'
                },
                cssMaxWidth: 1200,
                cssMaxHeight: 900,
                selectionStyle: {
                    cornerStyle: 'circle',
                    cornerSize: 10,
                    rotatingPointOffset: 70,
                    borderColor: '#ffbb3b',
                    cornerColor: '#ffbb3b'
                }
            });

            // Add event listeners
            this.editorInstance.on('imageLoaded', () => {
                console.log('Image loaded in editor');
                document.querySelector('.editor-loading')?.remove();
                this.editorInstance.resizeCanvasDimension({ width: 800, height: 600 });
            });
            
            window.addEventListener('resize', () => {
                if (this.editorInstance) {
                    this.editorInstance.ui.resizeEditor();
                }
            });

        } catch (error) {
            console.error('Error initializing image editor:', error);
            this.showError('Failed to initialize image editor: ' + error.message);
            document.querySelector('.editor-loading')?.remove();
        }
    }

    async cropImage(imageId) {
        try {
            console.log('Starting crop for image:', imageId);
            
            // Wait for Cropper.js to be loaded
            if (typeof Cropper === 'undefined') {
                console.log('Cropper.js not loaded, waiting...');
                await this.waitForCropper();
            }
            
            const imageElement = this.findImageElement(imageId);
            if (!imageElement) {
                console.error('Image element not found');
                return;
            }

            console.log('Found image element:', imageElement);
            this.currentCroppingImageId = imageId;
            const cropperImage = document.getElementById('cropperImage');
            
            if (!cropperImage) {
                console.error('Cropper image element not found');
                return;
            }

            // Show the modal first
            this.cropperModal = new bootstrap.Modal(document.getElementById('imageCropperModal'));
            this.cropperModal.show();

            // Reset cropper image and force browser to load it fresh
            cropperImage.src = '';
            // Add timestamp to prevent caching
            const imageUrl = imageElement.src + (imageElement.src.includes('?') ? '&' : '?') + 'timestamp=' + new Date().getTime();
            cropperImage.src = imageUrl;

            console.log('Setting up cropper image:', imageUrl);

            // Wait for image to load before initializing cropper
            await new Promise((resolve, reject) => {
                cropperImage.onload = () => {
                    console.log('Image loaded, initializing cropper');
                    try {
                        // Destroy existing cropper instance if it exists
                        if (this.cropperInstance) {
                            console.log('Destroying existing cropper instance');
                            this.cropperInstance.destroy();
                            this.cropperInstance = null;
                        }

                        // Initialize Cropper.js with more flexible options
                        this.cropperInstance = new Cropper(cropperImage, {
                            aspectRatio: NaN, // Free aspect ratio
                            viewMode: 2, // Restrict view to container
                            dragMode: 'move',
                            responsive: true,
                            restore: false,
                            autoCrop: true,
                            autoCropArea: 0.8,
                            background: true,
                            modal: true,
                            guides: true,
                            center: true,
                            highlight: true,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: true,
                            minContainerWidth: 300,
                            minContainerHeight: 300,
                            ready: () => {
                                console.log('Cropper is ready');
                                resolve();
                            }
                        });
                        
                        console.log('Cropper instance created:', this.cropperInstance);
                    } catch (error) {
                        console.error('Error in cropper initialization:', error);
                        reject(error);
                    }
                };
                
                cropperImage.onerror = (error) => {
                    console.error('Error loading image:', error);
                    reject(error);
                };
            });

            console.log('Modal shown');

        } catch (error) {
            console.error('Error in cropImage:', error);
            this.showError('Failed to initialize image cropper: ' + error.message);
        }
    }

    waitForCropper() {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const maxAttempts = 50; // 5 seconds max wait
            
            const checkCropper = () => {
                attempts++;
                if (typeof Cropper !== 'undefined') {
                    console.log('Cropper.js is now available');
                    resolve();
                } else if (attempts >= maxAttempts) {
                    reject(new Error('Cropper.js failed to load after 5 seconds'));
                } else {
                    setTimeout(checkCropper, 100);
                }
            };
            
            checkCropper();
        });
    }

    async waitForTuiEditor() {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const maxAttempts = 50; // 5 seconds max wait
            
            const checkTui = () => {
                attempts++;
                if (typeof tui !== 'undefined' && tui.ImageEditor) {
                    console.log('TUI Image Editor is now available');
                    resolve();
                } else if (attempts >= maxAttempts) {
                    reject(new Error('TUI Image Editor failed to load after 5 seconds'));
                } else {
                    setTimeout(checkTui, 100);
                }
            };
            
            checkTui();
        });
    }

    async saveEditedImage() {
        try {
            if (!this.editorInstance || !this.currentEditingImageId) {
                this.showError('No image is being edited.');
                return;
            }

            const saveButton = document.getElementById('saveEditedImage');
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveButton.disabled = true;

            const editedImageDataUrl = this.editorInstance.toDataURL({
                format: 'jpeg',
                quality: 0.9
            });
            
            const blob = await fetch(editedImageDataUrl).then(r => r.blob());
            
            const formData = new FormData();
            formData.append('image', blob, `edited-image-${this.currentEditingImageId}.jpg`);
            formData.append('business_id', this.businessId);
            formData.append('image_id', this.currentEditingImageId);
            formData.append('action', 'update');

            const response = await this.apiRequest('update', formData);
            
            if (response.success) {
                this.editorModal.hide();
                await this.loadImages(); // Reload images to show the updated one
                this.showSuccess('Image updated successfully');
            } else {
                // Add a more specific error message from the server if available
                throw new Error(response.message || 'API request to update image failed');
            }
        } catch (error) {
            console.error('Error saving edited image:', error);
            this.showError(`Failed to save edited image: ${error.message}`);
        } finally {
            const saveButton = document.getElementById('saveEditedImage');
            if(saveButton) {
                saveButton.innerHTML = 'Save Changes';
                saveButton.disabled = false;
            }
        }
    }

    async saveCroppedImage() {
        const saveButton = document.getElementById('saveCroppedImage');
        try {
            if (!this.cropperInstance || !this.currentCroppingImageId) {
                console.error('No cropper instance or no image ID');
                this.showError('No image to crop');
                return;
            }

            // Show loading state
            console.log('Starting save process');
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveButton.disabled = true;

            // Get cropped canvas
            console.log('Getting cropped canvas');
            const croppedCanvas = this.cropperInstance.getCroppedCanvas({
                width: 1920,
                height: 1080,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });

            if (!croppedCanvas) {
                throw new Error('Failed to crop image - no canvas generated');
            }

            // Convert canvas to blob
            console.log('Converting canvas to blob');
            const blob = await new Promise((resolve, reject) => {
                croppedCanvas.toBlob(
                    (b) => b ? resolve(b) : reject(new Error('Blob conversion failed')),
                    'image/jpeg',
                    0.95
                );
            });

            // Create form data and send request
            console.log('Preparing form data');
            const formData = new FormData();
            formData.append('image', blob, 'cropped-image.jpg');
            formData.append('business_id', this.businessId);
            formData.append('image_id', this.currentCroppingImageId);
            formData.append('action', 'update');

            console.log('Sending update request');
            const response = await this.apiRequest('update', formData);
            
            if (response.success) {
                console.log('Update successful');
                // Clean up
                this.cropperInstance.destroy();
                this.cropperInstance = null;
                this.currentCroppingImageId = null;
                
                // Hide modal
                this.cropperModal.hide();
                
                // Reload images
                await this.loadImages();
                this.showSuccess('Image cropped successfully');
            } else {
                throw new Error(response.message || 'Failed to save cropped image');
            }
        } catch (error) {
            console.error('Error in saveCroppedImage:', error);
            this.showError(error.message || 'Failed to save cropped image');
        } finally {
            // Reset save button
            if (saveButton) {
                saveButton.innerHTML = '<span class="button-text">Save Changes</span>';
                saveButton.disabled = false;
            }
        }
    }

    findImageElement(imageId) {
        // Try to find the image in main container first
        let imageElement = this.mainImageContainer?.querySelector(`[data-image-id="${imageId}"]`)?.closest('.main-image-wrapper')?.querySelector('img');
        
        // If not found in main, try gallery
        if (!imageElement) {
            imageElement = this.galleryContainer?.querySelector(`[data-image-id="${imageId}"]`)?.querySelector('img');
        }

        if (!imageElement) {
            this.showError('Image not found');
            return null;
        }

        return imageElement;
    }

    displayGalleryImages(images) {
        if (!this.galleryContainer) return;

        // Clear only the image items, not the whole container
        this.galleryContainer.querySelectorAll('.gallery-item').forEach(item => item.remove());

        if (!images || images.length === 0) {
            this.galleryContainer.innerHTML += `
                <div class="gallery-placeholder">
                    <i class="fas fa-images"></i>
                    <p>No gallery images uploaded</p>
                </div>
            `;
        } else {
            images.forEach(image => this.addGalleryImage(image));
        }
    }

    addGalleryImage(imageData) {
        if (!this.galleryContainer) return;

        // Remove placeholder if it exists
        const placeholder = this.galleryContainer.querySelector('.gallery-placeholder');
        if (placeholder) {
            placeholder.remove();
        }

        const imageUrl = `${imageData.file_path}?v=${new Date().getTime()}`;
        const item = document.createElement('div');
        item.className = 'gallery-item';
        item.dataset.imageId = imageData.id;
        item.innerHTML = `
            <div class="gallery-image-wrapper">
                <img src="${imageUrl}" alt="Gallery image" class="gallery-image">
                <div class="image-actions">
                    <button type="button" class="btn btn-primary btn-sm set-main" data-image-id="${imageData.id}">
                        <i class="fas fa-star"></i> Set as Main
                    </button>
                    <button type="button" class="btn btn-danger btn-sm delete-image" data-image-id="${imageData.id}">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `;
        this.galleryContainer.appendChild(item);

        // Add event listeners
        item.querySelector('.delete-image').addEventListener('click', () => 
            this.deleteImage(imageData.id, 'gallery')
        );
        item.querySelector('.set-main').addEventListener('click', () => 
            this.setMainImage(imageData.id)
        );
    }

    validateFile(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!allowedTypes.includes(file.type)) {
            this.showError('Invalid file type. Please upload JPG, PNG or GIF files only.');
            return false;
        }

        if (file.size > maxSize) {
            this.showError('File is too large. Maximum size is 5MB.');
            return false;
        }

        return true;
    }

    async apiRequest(action, data) {
        try {
            let options = {
                method: 'POST',
                credentials: 'same-origin'
            };

            // If data is FormData, use it directly
            if (data instanceof FormData) {
                data.append('action', action);
                options.body = data;
            } else {
                // For JSON data, create a new FormData
                const formData = new FormData();
                formData.append('action', action);
                for (const [key, value] of Object.entries(data)) {
                    formData.append(key, typeof value === 'object' ? JSON.stringify(value) : value);
                }
                options.body = formData;
            }

            const response = await fetch(this.apiEndpoint, options);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Server error');
            }

            return result;
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }

    showSuccess(message) {
        console.log(message);
        // You can customize this to show success messages in your UI
        // For now, we'll just log to console
    }

    showError(message) {
        console.error(message);
        // You can customize this to show errors in your UI
        alert(message);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the edit business page
    const businessId = document.querySelector('input[name="business_id"]')?.value || 
                      new URLSearchParams(window.location.search).get('id');
    
    if (businessId) {
        // Create image manager container
        const imageManagerContainer = document.createElement('div');
        imageManagerContainer.id = 'imageManager';
        imageManagerContainer.className = 'image-manager';
        
        // Find the gallery section and replace it
        const gallerySection = document.querySelector('.gallery-section');
        if (gallerySection) {
            gallerySection.parentNode.replaceChild(imageManagerContainer, gallerySection);
        } else {
            // If no gallery section exists, add to the form
            const form = document.querySelector('.edit-business-form');
            if (form) {
                form.appendChild(imageManagerContainer);
            }
        }
        
        // Initialize image manager
        new ImageManager({ businessId });
    }
}); 