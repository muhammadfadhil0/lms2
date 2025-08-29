<!-- Modal untuk melihat gambar fullscreen -->
<div id="imageViewerModal" class="modal-overlay hidden">
    <div class="modal-container">
        <div class="modal-content image-viewer-content">
            <!-- Header dengan tombol close -->
            <div class="image-viewer-header">
                <button id="closeImageViewer" class="close-btn">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            
            <!-- Area gambar -->
            <div class="image-viewer-main">
                <div class="image-navigation">
                    <button id="prevImage" class="nav-btn hidden">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <div class="image-container">
                        <img id="viewerImage" src="" alt="Gambar Postingan" class="viewer-image">
                    </div>
                    <button id="nextImage" class="nav-btn hidden">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Footer dengan info postingan -->
            <div class="image-viewer-footer">
                <div class="post-info">
                    <div class="post-author">
                        <div class="author-avatar">
                            <i class="ti ti-user"></i>
                        </div>
                        <div class="author-details">
                            <span class="author-name" id="viewerAuthorName"></span>
                            <span class="post-date" id="viewerPostDate"></span>
                        </div>
                    </div>
                    <div class="image-counter">
                        <span id="imageCounter">1 / 1</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal overlay - fullscreen background */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1;
    visibility: visible;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.modal-overlay.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.modal-container {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.image-viewer-content {
    max-width: 90vw;
    max-height: 90vh;
    width: auto;
    height: auto;
    padding: 0;
    background: #000;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.image-viewer-header {
    position: absolute;
    top: 0;
    right: 0;
    z-index: 1000;
    padding: 16px;
}

.close-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s;
}

.close-btn:hover {
    background: rgba(0, 0, 0, 0.9);
}

.image-viewer-main {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.image-navigation {
    display: flex;
    align-items: center;
    width: 100%;
    height: 100%;
}

.nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s;
    z-index: 1001;
}

.nav-btn:hover {
    background: rgba(0, 0, 0, 0.9);
}

#prevImage {
    left: 16px;
}

#nextImage {
    right: 16px;
}

.image-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 300px;
}

.viewer-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 4px;
}

.image-viewer-footer {
    background: rgba(0, 0, 0, 0.8);
    padding: 16px 20px;
    color: white;
}

.post-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.post-author {
    display: flex;
    align-items: center;
    gap: 12px;
}

.author-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f97316;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.author-details {
    display: flex;
    flex-direction: column;
}

.author-name {
    font-weight: 600;
    font-size: 14px;
}

.post-date {
    font-size: 12px;
    opacity: 0.8;
}

.image-counter {
    font-size: 14px;
    opacity: 0.9;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .image-viewer-content {
        max-width: 95vw;
        max-height: 95vh;
    }
    
    .image-viewer-header {
        padding: 12px;
    }
    
    .close-btn {
        width: 36px;
        height: 36px;
    }
    
    .nav-btn {
        width: 40px;
        height: 40px;
    }
    
    #prevImage {
        left: 8px;
    }
    
    #nextImage {
        right: 8px;
    }
    
    .image-viewer-footer {
        padding: 12px 16px;
    }
    
    .post-author {
        gap: 8px;
    }
    
    .author-avatar {
        width: 32px;
        height: 32px;
    }
    
    .author-name {
        font-size: 13px;
    }
    
    .post-date {
        font-size: 11px;
    }
    
    .image-counter {
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .image-viewer-content {
        max-width: 100vw;
        max-height: 100vh;
        border-radius: 0;
    }
    
    .image-container {
        min-height: 250px;
    }
}
</style>
