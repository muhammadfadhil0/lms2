/**
 * PhotoSwipe Image Viewer for LMS
 * Simple and reliable image gallery integration
 */

// Global variables
let photoSwipeReady = false;
let photoSwipeLibraryLoaded = false;

// Load PhotoSwipe assets
function loadPhotoSwipeAssets() {
    return new Promise((resolve, reject) => {
        if (photoSwipeLibraryLoaded) {
            resolve();
            return;
        }

        console.log('Loading PhotoSwipe assets...');

        // Load CSS files first
        const cssFiles = [
            'https://cdn.jsdelivr.net/npm/photoswipe@4.1.3/dist/photoswipe.css',
            'https://cdn.jsdelivr.net/npm/photoswipe@4.1.3/dist/default-skin/default-skin.css'
        ];

        let cssLoaded = 0;
        cssFiles.forEach(href => {
            if (!document.querySelector(`link[href="${href}"]`)) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                link.onload = () => {
                    cssLoaded++;
                    if (cssLoaded === cssFiles.length) {
                        // CSS loaded, now load JS
                        loadPhotoSwipeJS().then(resolve).catch(reject);
                    }
                };
                link.onerror = () => reject(new Error('Failed to load PhotoSwipe CSS'));
                document.head.appendChild(link);
            } else {
                cssLoaded++;
                if (cssLoaded === cssFiles.length) {
                    loadPhotoSwipeJS().then(resolve).catch(reject);
                }
            }
        });
    });
}

function loadPhotoSwipeJS() {
    return new Promise((resolve, reject) => {
        // Check if already loaded
        if (window.PhotoSwipe && window.PhotoSwipeUI_Default) {
            photoSwipeLibraryLoaded = true;
            resolve();
            return;
        }

        // Load PhotoSwipe JS
        const script1 = document.createElement('script');
        script1.src = 'https://cdn.jsdelivr.net/npm/photoswipe@4.1.3/dist/photoswipe.min.js';
        script1.onload = () => {
            console.log('PhotoSwipe main script loaded');
            
            // Load PhotoSwipe UI
            const script2 = document.createElement('script');
            script2.src = 'https://cdn.jsdelivr.net/npm/photoswipe@4.1.3/dist/photoswipe-ui-default.min.js';
            script2.onload = () => {
                console.log('PhotoSwipe UI script loaded');
                photoSwipeLibraryLoaded = true;
                resolve();
            };
            script2.onerror = () => reject(new Error('Failed to load PhotoSwipe UI'));
            document.head.appendChild(script2);
        };
        script1.onerror = () => reject(new Error('Failed to load PhotoSwipe'));
        document.head.appendChild(script1);
    });
}

// Setup event listeners for image clicks
function setupPhotoSwipeListeners() {
    console.log('Setting up PhotoSwipe event listeners...');
    
    document.addEventListener('click', function(e) {
        console.log('Click detected on:', e.target);
        console.log('Target classes:', e.target.className);
        console.log('Has post-image class:', e.target.classList.contains('post-image'));
        
        // Check if clicked element is a post image
        if (e.target.classList.contains('post-image')) {
            e.preventDefault();
            console.log('Post image clicked:', e.target.src);
            
            const postElement = e.target.closest('[data-post-id]');
            console.log('Found post element:', postElement);
            
            if (postElement) {
                const imageIndex = parseInt(e.target.dataset.imageIndex) || 0;
                const postId = postElement.dataset.postId;
                console.log('Opening gallery for post:', postId, 'at index:', imageIndex);
                openPhotoSwipeGallery(postElement, imageIndex);
            } else {
                console.error('Post element not found');
            }
        }
    });
    
    console.log('PhotoSwipe event listeners setup complete');
}

// Open PhotoSwipe gallery
function openPhotoSwipeGallery(postElement, startIndex = 0) {
    if (!window.PhotoSwipe || !window.PhotoSwipeUI_Default) {
        console.error('PhotoSwipe not loaded yet');
        return;
    }

    const images = postElement.querySelectorAll('.post-image');
    if (images.length === 0) {
        console.error('No images found in post');
        return;
    }

    console.log('Opening gallery with', images.length, 'images, starting at index', startIndex);

    // Prepare gallery items
    const galleryItems = Array.from(images).map((img, index) => {
        // Get natural dimensions or use defaults
        let width = img.naturalWidth || 800;
        let height = img.naturalHeight || 600;
        
        // If dimensions are 0, set reasonable defaults
        if (width === 0 || height === 0) {
            width = 800;
            height = 600;
        }

        return {
            src: img.src,
            w: width,
            h: height,
            title: getImageTitle(postElement, index + 1, images.length)
        };
    });

    // Get PhotoSwipe element
    const pswpElement = document.querySelector('.pswp');
    if (!pswpElement) {
        console.error('PhotoSwipe .pswp element not found');
        return;
    }

    // PhotoSwipe options
    const options = {
        index: startIndex,
        bgOpacity: 0.9,
        showHideOpacity: true,
        loop: images.length > 1,
        pinchToClose: true,
        closeOnScroll: false,
        closeOnVerticalDrag: true,
        escKey: true,
        arrowKeys: true,
        history: false,
        
        // UI elements
        shareEl: false,
        fullscreenEl: true,
        zoomEl: true,
        counterEl: images.length > 1,
        arrowEl: images.length > 1,
        captionEl: true,
        
        // Zoom settings
        maxSpreadZoom: 3,
        getDoubleTapZoom: function(isMouseClick, item) {
            return isMouseClick ? 1.5 : (item.initialZoomLevel < 0.7 ? 1 : 1.5);
        }
    };

    // Initialize PhotoSwipe
    const gallery = new window.PhotoSwipe(pswpElement, window.PhotoSwipeUI_Default, galleryItems, options);
    
    // Event listeners
    gallery.listen('close', function() {
        console.log('PhotoSwipe gallery closed');
    });

    gallery.listen('afterChange', function() {
        console.log('PhotoSwipe image changed to index:', gallery.getCurrentIndex());
    });

    // Open the gallery
    gallery.init();
}

// Get image title/caption
function getImageTitle(postElement, imageNumber, totalImages) {
    const authorElement = postElement.querySelector('h3.font-semibold, .font-semibold');
    const timeElement = postElement.querySelector('p.text-xs, .text-xs');
    
    let title = '';
    
    if (authorElement) {
        title += 'Posted by ' + authorElement.textContent.trim();
    }
    
    if (timeElement && timeElement.textContent.includes('•')) {
        const timePart = timeElement.textContent.split('•').pop().trim();
        if (timePart) {
            title += (title ? ' • ' : '') + timePart;
        }
    }
    
    if (totalImages > 1) {
        title += (title ? ' • ' : '') + `Image ${imageNumber} of ${totalImages}`;
    }
    
    return title || `Image ${imageNumber}`;
}

// Initialize PhotoSwipe when DOM is ready
function initPhotoSwipe() {
    console.log('Initializing PhotoSwipe...');
    
    loadPhotoSwipeAssets()
        .then(() => {
            console.log('PhotoSwipe assets loaded successfully');
            setupPhotoSwipeListeners();
            photoSwipeReady = true;
            console.log('PhotoSwipe is ready!');
        })
        .catch(error => {
            console.error('Failed to load PhotoSwipe:', error);
        });
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPhotoSwipe);
} else {
    initPhotoSwipe();
}

// Export for global access
window.initPhotoSwipe = initPhotoSwipe;
window.openPhotoSwipeGallery = openPhotoSwipeGallery;