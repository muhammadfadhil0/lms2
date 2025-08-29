/**
 * PhotoSwipe Image Viewer Integration
 * Modern, powerful image gallery with zoom, swipe, and responsive features
 */
class PhotoSwipeImageViewer {
    constructor() {
        this.pswpElement = null;
        this.gallery = null;
        this.loadingPromise = null;
        this.init();
    }
    
    init() {
        this.loadPhotoSwipeAssets().then(() => {
            this.setupEventListeners();
            console.log('PhotoSwipe fully loaded and ready');
        }).catch(err => {
            console.error('Failed to load PhotoSwipe:', err);
        });
    }
    
    loadPhotoSwipeAssets() {
        if (this.loadingPromise) {
            return this.loadingPromise;
        }
        
        this.loadingPromise = new Promise((resolve, reject) => {
            // Check if PhotoSwipe is already loaded
            if (window.PhotoSwipe && window.PhotoSwipeUI_Default) {
                resolve();
                return;
            }
            
            // Load PhotoSwipe CSS
            if (!document.querySelector('link[href*="photoswipe"]')) {
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'https://cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe.min.css';
                document.head.appendChild(css);
                
                const defaultSkin = document.createElement('link');
                defaultSkin.rel = 'stylesheet';
                defaultSkin.href = 'https://cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/default-skin/default-skin.min.css';
                document.head.appendChild(defaultSkin);
            }
            
            // Load PhotoSwipe JS
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe.min.js';
            script.onload = () => {
                const uiScript = document.createElement('script');
                uiScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe-ui-default.min.js';
                uiScript.onload = () => {
                    console.log('PhotoSwipe scripts loaded successfully');
                    resolve();
                };
                uiScript.onerror = () => reject(new Error('Failed to load PhotoSwipe UI'));
                document.head.appendChild(uiScript);
            };
            script.onerror = () => reject(new Error('Failed to load PhotoSwipe'));
            document.head.appendChild(script);
        });
        
        return this.loadingPromise;
    }
    
    setupEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.post-image')) {
                console.log('PhotoSwipe: Post image clicked');
                e.preventDefault();
                e.stopPropagation();
                
                const postElement = e.target.closest('[data-post-id]');
                if (postElement) {
                    const postId = postElement.dataset.postId;
                    const imageIndex = parseInt(e.target.dataset.imageIndex) || 0;
                    this.openGallery(postId, imageIndex);
                } else {
                    console.warn('PhotoSwipe: Post element not found');
                }
            }
        });
    }
    
    openGallery(postId, imageIndex = 0) {
        const postElement = document.querySelector(`[data-post-id="${postId}"]`);
        if (!postElement) {
            console.error('PhotoSwipe: Post element not found for ID:', postId);
            return;
        }
        
        const images = postElement.querySelectorAll('.post-image');
        if (images.length === 0) {
            console.error('PhotoSwipe: No images found in post:', postId);
            return;
        }
        
        console.log(`PhotoSwipe: Opening gallery for post ${postId}, image ${imageIndex}, total images: ${images.length}`);
        
        // Wait for PhotoSwipe to load
        this.loadPhotoSwipeAssets().then(() => {
            this.initializeGallery(images, imageIndex, postElement);
        }).catch(err => {
            console.error('PhotoSwipe: Failed to load assets:', err);
        });
    }
    
    initializeGallery(images, imageIndex, postElement) {
        if (!window.PhotoSwipe || !window.PhotoSwipeUI_Default) {
            console.error('PhotoSwipe: Libraries not available');
            return;
        }
        
        // Prepare gallery items
        const items = Array.from(images).map((img, index) => {
            // Get image dimensions
            let width = img.naturalWidth || img.width || 800;
            let height = img.naturalHeight || img.height || 600;
            
            // Fallback for images that haven't loaded yet
            if (width === 0 || height === 0) {
                width = 800;
                height = 600;
            }
            
            return {
                src: img.src,
                w: width,
                h: height,
                title: this.getImageCaption(postElement, img, index + 1, images.length)
            };
        });
        
        console.log('PhotoSwipe: Gallery items prepared:', items);
        
        // Get PhotoSwipe element
        this.pswpElement = document.querySelector('.pswp');
        if (!this.pswpElement) {
            console.error('PhotoSwipe: .pswp element not found');
            return;
        }
        
        // PhotoSwipe options
        const options = {
            index: imageIndex,
            bgOpacity: 0.9,
            showHideOpacity: true,
            loop: images.length > 1,
            pinchToClose: true,
            closeOnScroll: false,
            closeOnVerticalDrag: true,
            mouseUsed: false,
            escKey: true,
            arrowKeys: true,
            history: false,
            galleryUID: postElement.dataset.postId,
            
            // UI options
            shareEl: false,
            fullscreenEl: true,
            zoomEl: true,
            counterEl: images.length > 1,
            arrowEl: images.length > 1,
            captionEl: true,
            
            // Zoom options
            maxSpreadZoom: 3,
            getDoubleTapZoom: function(isMouseClick, item) {
                if(isMouseClick) {
                    return 1.5;
                } else {
                    return item.initialZoomLevel < 0.7 ? 1 : 1.5;
                }
            },
            
            // Animation options
            showAnimationDuration: 333,
            hideAnimationDuration: 333,
            
            // Error handling
            errorMsg: '<div class="pswp__error-msg"><a href="%url%" target="_blank">The image</a> could not be loaded.</div>'
        };
        
        // Initialize PhotoSwipe
        this.gallery = new window.PhotoSwipe(this.pswpElement, window.PhotoSwipeUI_Default, items, options);
        
        // Custom events
        this.gallery.listen('beforeChange', () => {
            console.log('PhotoSwipe: Image changed to index:', this.gallery.getCurrentIndex());
        });
        
        this.gallery.listen('close', () => {
            console.log('PhotoSwipe: Gallery closed');
            this.gallery = null;
        });
        
        this.gallery.listen('destroy', () => {
            console.log('PhotoSwipe: Gallery destroyed');
            this.gallery = null;
        });
        
        // Initialize and open
        console.log('PhotoSwipe: Opening gallery...');
        this.gallery.init();
    }
    
    getImageCaption(postElement, img, imageNumber, totalImages) {
        // Get post info for caption
        const authorElement = postElement.querySelector('h3.font-semibold');
        const dateElement = postElement.querySelector('p.text-xs');
        
        const author = authorElement?.textContent || 'Unknown';
        const dateText = this.extractTimeFromText(dateElement?.textContent || '');
        
        let caption = `Posted by ${author}`;
        if (dateText) {
            caption += ` • ${dateText}`;
        }
        
        if (totalImages > 1) {
            caption += ` • Image ${imageNumber} of ${totalImages}`;
        }
        
        return caption;
    }
    
    extractTimeFromText(text) {
        // Extract time info from text like "Guru • 5 menit yang lalu"
        const matches = text.match(/•\s*(.+?)(?:\s*•|$)/);
        return matches ? matches[1].trim() : text;
    }
    
    // Method to manually open gallery with custom items
    openCustomGallery(items, index = 0) {
        this.loadPhotoSwipeAssets().then(() => {
            if (!window.PhotoSwipe || !window.PhotoSwipeUI_Default) {
                console.error('PhotoSwipe not loaded yet');
                return;
            }
            
            this.pswpElement = document.querySelector('.pswp');
            if (!this.pswpElement) {
                console.error('PhotoSwipe: .pswp element not found');
                return;
            }
            
            const options = {
                index: index,
                bgOpacity: 0.9,
                showHideOpacity: true,
                shareEl: false,
                fullscreenEl: true,
                zoomEl: true,
                counterEl: items.length > 1,
                arrowEl: items.length > 1,
                captionEl: true
            };
            
            this.gallery = new window.PhotoSwipe(this.pswpElement, window.PhotoSwipeUI_Default, items, options);
            this.gallery.init();
        });
    }
    
    // Method to check if PhotoSwipe is ready
    isReady() {
        return !!(window.PhotoSwipe && window.PhotoSwipeUI_Default);
    }
}

// Initialize PhotoSwipe when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing PhotoSwipe image viewer...');
    window.photoSwipeViewer = new PhotoSwipeImageViewer();
    
    // Add a small delay to ensure everything is loaded
    setTimeout(() => {
        console.log('PhotoSwipe image viewer ready');
    }, 500);
});
