/**
 * Simple Image Viewer for LMS
 * Pure JavaScript implementation without external dependencies
 * Features: Modal viewer, navigation, responsive design, keyboard support
 */

// Global variables for simple viewer
let currentImages = [];
let currentIndex = 0;

// Simple image viewer fallback if PhotoSwipe fails
function createSimpleImageViewer() {
    console.log('Creating simple image viewer fallback...');
    
    // Remove existing modal if any
    const existingModal = document.getElementById('simpleImageModal');
    if (existingModal) {
        existingModal.remove();
        console.log('Removed existing modal');
    }
    
    // Remove existing styles
    const existingStyles = document.getElementById('simpleViewerStyles');
    if (existingStyles) {
        existingStyles.remove();
    }
    
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
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        " onclick="handleBackdropClick(event)">
            <div style="
                position: absolute;
                top: 15px;
                right: 25px;
                color: white;
                font-size: 35px;
                cursor: pointer;
                z-index: 10000;
                font-weight: bold;
                text-shadow: 0 0 10px rgba(0,0,0,0.8);
                transition: color 0.2s ease;
            " onclick="closeSimpleViewer()" onmouseover="this.style.color='#ff6b6b'" onmouseout="this.style.color='white'">&times;</div>
            <img id="simpleViewerImage" style="
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                max-width: 70%;
                max-height: 70%;
                object-fit: contain;
                cursor: default;
                border-radius: 8px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.6);
                transition: transform 0.3s ease;
            " onclick="event.stopPropagation()">
            <div id="simpleViewerNav" style="
                position: absolute;
                bottom: 30px;
                left: 50%;
                transform: translateX(-50%);
                color: white;
                text-align: center;
                padding: 15px 20px;
                border-radius: 25px;
                backdrop-filter: blur(5px);
            " onclick="event.stopPropagation()">
                <button onclick="navigateSimple(-1)" style="
                    background: rgba(255,255,255,0.2);
                    border: none;
                    color: white;
                    padding: 12px 16px;
                    margin: 0 8px;
                    cursor: pointer;
                    border-radius: 8px;
                    font-size: 16px;
                    transition: background 0.2s ease;
                " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">‹ Prev</button>
                <span id="simpleCounter" style="margin: 0 15px; font-weight: 500;">1 / 1</span>
                <button onclick="navigateSimple(1)" style="
                    background: rgba(255,255,255,0.2);
                    border: none;
                    color: white;
                    padding: 12px 16px;
                    margin: 0 8px;
                    cursor: pointer;
                    border-radius: 8px;
                    font-size: 16px;
                    transition: background 0.2s ease;
                " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">Next ›</button>
            </div>
        </div>
    `;
    
    // Create style element
    const styleHTML = `
        <style id="simpleViewerStyles">
            @media (max-width: 768px)  {
                #simpleViewerImage {
                    max-width: 65% !important;
                    max-height: 55% !important;
                }
                #simpleViewerNav {
                    bottom: 20px !important;
                    padding: 8px 12px !important;
                }
                #simpleViewerNav button {
                    padding: 6px 8px !important;
                    margin: 0 4px !important;
                    font-size: 12px !important;
                }
                #simpleViewerNav span {
                    margin: 0 6px !important;
                    font-size: 12px !important;
                }
                #simpleImageModal div:first-child {
                    top: 10px !important;
                    right: 15px !important;
                    font-size: 30px !important;
                }
            }
            
            @media (max-width: 480px) {
                #simpleViewerImage {
                    max-width: 70% !important;
                    max-height: 50% !important;
                }
                #simpleViewerNav {
                    bottom: 15px !important;
                    padding: 6px 8px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                }
                #simpleViewerNav button {
                    padding: 4px 6px !important;
                    margin: 0 2px !important;
                    font-size: 11px !important;
                    min-width: 40px !important;
                }
                #simpleViewerNav span {
                    margin: 0 4px !important;
                    font-size: 11px !important;
                    white-space: nowrap !important;
                }
            }
        </style>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.head.insertAdjacentHTML('beforeend', styleHTML);
    console.log('New modal created with updated styles - Desktop: 70%, Tablet: 75%, Mobile: 80%');
}

// Handle backdrop click to close viewer
function handleBackdropClick(event) {
    // Only close if clicking the backdrop (not the image or navigation)
    if (event.target.id === 'simpleImageModal') {
        closeSimpleViewer();
    }
}

function openSimpleViewer(images, startIndex = 0) {
    currentImages = images;
    currentIndex = startIndex;
    
    const modal = document.getElementById('simpleImageModal');
    const img = document.getElementById('simpleViewerImage');
    const counter = document.getElementById('simpleCounter');
    
    if (modal && img && counter) {
        // Set image and counter
        img.src = images[currentIndex];
        counter.textContent = `${currentIndex + 1} / ${images.length}`;
        
        // Show modal with animation
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Trigger fade in animation
        setTimeout(() => {
            modal.style.opacity = '1';
        }, 10);
    }
}

function closeSimpleViewer() {
    const modal = document.getElementById('simpleImageModal');
    if (modal) {
        // Fade out animation
        modal.style.opacity = '0';
        
        // Hide modal after animation completes
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
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
        // Add a subtle zoom animation on image change
        img.style.transform = 'translate(-50%, -50%) scale(0.95)';
        
        setTimeout(() => {
            img.src = currentImages[currentIndex];
            counter.textContent = `${currentIndex + 1} / ${currentImages.length}`;
            img.style.transform = 'translate(-50%, -50%) scale(1)';
        }, 150);
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
                
                // Always use simple viewer
                openSimpleViewer(images, imageIndex);
            }
        }
    });
    
    console.log('Image viewer initialized');
}

// PhotoSwipe integration (if available) - DEPRECATED, always use simple viewer
function openPhotoSwipeGallery(postElement, startIndex = 0) {
    const images = postElement.querySelectorAll('.post-image');
    if (images.length === 0) return;

    // Always use simple viewer instead
    const imageList = Array.from(images).map(img => img.src);
    openSimpleViewer(imageList, startIndex);
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

// Expose functions globally for external access
window.openSimpleViewer = openSimpleViewer;
window.closeSimpleViewer = closeSimpleViewer;
window.navigateSimple = navigateSimple;
window.handleBackdropClick = handleBackdropClick;

/**
 * USAGE DOCUMENTATION:
 * 
 * This file provides a simple image viewer for LMS posts.
 * It automatically detects images with class 'post-image' and opens them in a modal.
 * 
 * Features:
 * - Click any image with class 'post-image' to open viewer
 * - Click backdrop (dark area) to close
 * - ESC key to close
 * - Arrow keys to navigate between images
 * - Responsive design (70% desktop, 75% tablet, 80% mobile)
 * - Smooth animations and transitions
 * 
 * No external dependencies required.
 * No configuration needed - just include this file in your HTML.
 */
