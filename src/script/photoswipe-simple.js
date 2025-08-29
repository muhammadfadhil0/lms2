/**
 * Simple PhotoSwipe Implementation for LMS
 * Uses local/fallback approach for better reliability
 */

// Simple image viewer fallback if PhotoSwipe fails
function createSimpleImageViewer() {
    console.log('Creating simple image viewer fallback...');
    
    // Create modal HTML
    const modalHTML = `
        <div id="simpleImageModal" style="
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            cursor: pointer;
        ">
            <div style="
                position: absolute;
                top: 10px;
                right: 20px;
                color: white;
                font-size: 30px;
                cursor: pointer;
                z-index: 10000;
            " onclick="closeSimpleViewer()">&times;</div>
            <img id="simpleViewerImage" style="
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                max-width: 90%;
                max-height: 90%;
                object-fit: contain;
            ">
            <div id="simpleViewerNav" style="
                position: absolute;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                color: white;
                text-align: center;
            ">
                <button onclick="navigateSimple(-1)" style="
                    background: rgba(255,255,255,0.2);
                    border: none;
                    color: white;
                    padding: 10px 15px;
                    margin: 0 5px;
                    cursor: pointer;
                    border-radius: 5px;
                ">‹ Prev</button>
                <span id="simpleCounter">1 / 1</span>
                <button onclick="navigateSimple(1)" style="
                    background: rgba(255,255,255,0.2);
                    border: none;
                    color: white;
                    padding: 10px 15px;
                    margin: 0 5px;
                    cursor: pointer;
                    border-radius: 5px;
                ">Next ›</button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

let currentImages = [];
let currentIndex = 0;

function openSimpleViewer(images, startIndex = 0) {
    currentImages = images;
    currentIndex = startIndex;
    
    const modal = document.getElementById('simpleImageModal');
    const img = document.getElementById('simpleViewerImage');
    const counter = document.getElementById('simpleCounter');
    
    if (modal && img && counter) {
        img.src = images[currentIndex];
        counter.textContent = `${currentIndex + 1} / ${images.length}`;
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeSimpleViewer() {
    const modal = document.getElementById('simpleImageModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function navigateSimple(direction) {
    if (currentImages.length <= 1) return;
    
    currentIndex += direction;
    if (currentIndex < 0) currentIndex = currentImages.length - 1;
    if (currentIndex >= currentImages.length) currentIndex = 0;
    
    const img = document.getElementById('simpleViewerImage');
    const counter = document.getElementById('simpleCounter');
    
    if (img && counter) {
        img.src = currentImages[currentIndex];
        counter.textContent = `${currentIndex + 1} / ${currentImages.length}`;
    }
}

// ESC key support
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSimpleViewer();
    } else if (e.key === 'ArrowLeft') {
        navigateSimple(-1);
    } else if (e.key === 'ArrowRight') {
        navigateSimple(1);
    }
});

// Main image viewer initialization
function initImageViewer() {
    console.log('Initializing image viewer...');
    
    // Create simple viewer as fallback
    createSimpleImageViewer();
    
    // Setup click listeners for post images
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('post-image')) {
            e.preventDefault();
            console.log('Image clicked:', e.target.src);
            
            const postElement = e.target.closest('[data-post-id]');
            if (postElement) {
                const images = Array.from(postElement.querySelectorAll('.post-image')).map(img => img.src);
                const imageIndex = parseInt(e.target.dataset.imageIndex) || 0;
                
                console.log('Opening viewer with', images.length, 'images, starting at index', imageIndex);
                
                // Try to use PhotoSwipe first, fallback to simple viewer
                if (window.PhotoSwipe && window.PhotoSwipeUI_Default) {
                    openPhotoSwipeGallery(postElement, imageIndex);
                } else {
                    openSimpleViewer(images, imageIndex);
                }
            }
        }
    });
    
    console.log('Image viewer initialized');
}

// PhotoSwipe integration (if available)
function openPhotoSwipeGallery(postElement, startIndex = 0) {
    const images = postElement.querySelectorAll('.post-image');
    if (images.length === 0) return;

    const galleryItems = Array.from(images).map((img) => ({
        src: img.src,
        w: img.naturalWidth || 800,
        h: img.naturalHeight || 600,
        title: getImageTitle(postElement, Array.from(images).indexOf(img) + 1, images.length)
    }));

    const pswpElement = document.querySelector('.pswp');
    if (!pswpElement) {
        console.warn('PhotoSwipe element not found, using fallback');
        openSimpleViewer(galleryItems.map(item => item.src), startIndex);
        return;
    }

    const options = {
        index: startIndex,
        bgOpacity: 0.9,
        showHideOpacity: true,
        shareEl: false,
        fullscreenEl: true,
        zoomEl: true,
        counterEl: images.length > 1,
        arrowEl: images.length > 1,
        captionEl: true
    };

    const gallery = new window.PhotoSwipe(pswpElement, window.PhotoSwipeUI_Default, galleryItems, options);
    gallery.init();
}

function getImageTitle(postElement, imageNumber, totalImages) {
    const authorElement = postElement.querySelector('h3.font-semibold, .font-semibold');
    let title = authorElement ? `Posted by ${authorElement.textContent.trim()}` : '';
    
    if (totalImages > 1) {
        title += (title ? ' • ' : '') + `Image ${imageNumber} of ${totalImages}`;
    }
    
    return title || `Image ${imageNumber}`;
}

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initImageViewer);
} else {
    initImageViewer();
}

// Expose functions globally
window.openSimpleViewer = openSimpleViewer;
window.closeSimpleViewer = closeSimpleViewer;
window.navigateSimple = navigateSimple;
