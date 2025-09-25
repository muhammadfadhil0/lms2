/**
 * Search System Module
 * Reusable search functionality for all pages
 * 
 * Usage:
 * const searchSystem = new SearchSystem({
 *     searchButtonSelector: '.search-btn',
 *     otherButtonsSelector: '.search-other-buttons', 
 *     resultsContainerSelector: '.search-results-container',
 *     cardSelector: '.search-card',
 *     apiEndpoint: '../logic/search-kelas-api.php',
 *     searchFields: ['namaKelas', 'mataPelajaran', 'deskripsi']
 * });
 */

class SearchSystem {
    constructor(options = {}) {
        // Default configuration
        this.config = {
            searchButtonSelector: '.search-btn',
            otherButtonsSelector: '.search-other-buttons',
            resultsContainerSelector: '.search-results-container', 
            cardSelector: '.search-card',
            apiEndpoint: '../logic/search-kelas-api.php',
            searchFields: ['namaKelas', 'mataPelajaran', 'deskripsi'],
            debounceDelay: 800,
            minSearchLength: 1,
            ...options
        };
        
        this.isSearchActive = false;
        this.searchTimeout = null;
        this.originalCards = [];
        this.currentQuery = '';
        
        this.init();
    }
    
    init() {
        this.createSearchElements();
        this.bindEvents();
        this.storeOriginalCards();
    }
    
    createSearchElements() {
        const searchBtn = document.querySelector(this.config.searchButtonSelector);
        if (!searchBtn) {
            console.error('Search button not found');
            return;
        }
        
        // Create search container dengan styling yang tidak mengganggu
        const container = document.createElement('div');
        container.className = 'search-container';
        container.style.display = 'inline-flex';
        container.style.alignItems = 'center';
        
        // Create search input
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'search-input';
        
        // Set placeholder based on page context
        const isUjianPage = window.location.pathname.includes('ujian');
        const isSiswaPage = window.location.pathname.includes('user') || window.location.pathname.includes('siswa');
        
        if (isUjianPage) {
            input.placeholder = 'Cari ujian...';
        } else if (isSiswaPage) {
            input.placeholder = 'Cari kelas...';
        } else {
            input.placeholder = 'Cari kelas...';
        }
        
        // Create search button elements
        const searchIcon = searchBtn.querySelector('i');
        const originalIconClass = searchIcon.className;
        
        // Preserve original button classes and styles
        const originalBtnClasses = searchBtn.className;
        
        // Create loader
        const loader = document.createElement('div');
        loader.className = 'search-loader';
        loader.innerHTML = '<div class="spinner"></div>';
        
        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'search-close';
        closeBtn.innerHTML = '<i class="ti ti-x"></i>';
        
        // Store references
        this.elements = {
            container,
            input,
            searchBtn,
            searchIcon,
            originalIconClass,
            originalBtnClasses,
            loader,
            closeBtn,
            otherButtons: document.querySelector(this.config.otherButtonsSelector),
            resultsContainer: document.querySelector(this.config.resultsContainerSelector)
        };
        
        // Wrap the search button instead of replacing
        const parent = searchBtn.parentNode;
        parent.insertBefore(container, searchBtn);
        container.appendChild(input);
        container.appendChild(searchBtn);
        searchBtn.appendChild(loader);
        searchBtn.appendChild(closeBtn);
    }
    
    bindEvents() {
        const { searchBtn, input, closeBtn } = this.elements;
        
        // Search button click
        searchBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (!this.isSearchActive) {
                this.activateSearch();
            }
        });
        
        // Input events
        input.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });
        
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.deactivateSearch();
            }
        });
        
        // Close button click
        closeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.deactivateSearch();
        });
        
        // Click outside to close
        document.addEventListener('click', (e) => {
            if (this.isSearchActive && !this.elements.container.contains(e.target)) {
                this.deactivateSearch();
            }
        });
    }
    
    activateSearch() {
        const { container, input, searchIcon, otherButtons, searchBtn } = this.elements;
        
        this.isSearchActive = true;
        
        // Add classes for animations
        container.classList.add('searching');
        input.classList.add('active');
        searchBtn.classList.add('searching');
        
        // Hide other buttons
        if (otherButtons) {
            otherButtons.classList.add('hidden');
        }
        
        // Hide original search icon
        searchIcon.classList.add('hidden');
        
        // Focus input with delay for animation
        setTimeout(() => {
            input.focus();
        }, 300);
    }
    
    deactivateSearch() {
        const { container, input, searchIcon, otherButtons, searchBtn, loader, closeBtn, originalBtnClasses } = this.elements;
        
        this.isSearchActive = false;
        this.currentQuery = '';
        
        // Clear search timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Remove classes
        container.classList.remove('searching');
        input.classList.remove('active');
        searchBtn.classList.remove('searching');
        
        // Restore original button classes
        searchBtn.className = originalBtnClasses;
        
        // Show other buttons
        if (otherButtons) {
            otherButtons.classList.remove('hidden');
        }
        
        // Show original search icon
        searchIcon.classList.remove('hidden');
        
        // Hide loader and close button
        loader.classList.remove('active');
        closeBtn.classList.remove('active');
        
        // Clear input
        input.value = '';
        
        // Restore original cards
        this.restoreOriginalCards();
    }
    
    handleSearchInput(query) {
        this.currentQuery = query.trim();
        
        // Clear existing timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // If empty query, restore all cards
        if (this.currentQuery.length === 0) {
            this.restoreOriginalCards();
            return;
        }
        
        // Show loader
        this.showLoader();
        
        // Set timeout for debounced search
        this.searchTimeout = setTimeout(() => {
            this.performSearch(this.currentQuery);
        }, this.config.debounceDelay);
    }
    
    showLoader() {
        const { loader, closeBtn } = this.elements;
        loader.classList.add('active');
        closeBtn.classList.remove('active');
    }
    
    hideLoader() {
        const { loader, closeBtn } = this.elements;
        loader.classList.remove('active');
        closeBtn.classList.add('active');
    }
    
    async performSearch(query) {
        try {
            const response = await fetch(`${this.config.apiEndpoint}?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            this.hideLoader();
            
            if (data.success) {
                this.updateSearchResults(data.data, query);
            } else {
                console.error('Search failed:', data.message);
                this.showNoResults();
            }
        } catch (error) {
            console.error('Search error:', error);
            this.hideLoader();
            this.showNoResults();
        }
    }
    
    updateSearchResults(results, query) {
        const cards = document.querySelectorAll(this.config.cardSelector);
        const resultsContainer = this.elements.resultsContainer;
        
        // Add searching class to container
        if (resultsContainer) {
            resultsContainer.classList.add('searching');
        }
        
        // First, fade out all cards
        cards.forEach(card => {
            card.classList.remove('search-match');
            card.classList.add('fade-out');
        });
        
        // After fade out, hide non-matching cards and show matching ones
        setTimeout(() => {
            if (results.length === 0) {
                // Hide all cards if no results
                cards.forEach(card => {
                    card.classList.add('search-hidden');
                });
                this.showNoResults();
                return;
            }
            
            // Hide all cards first
            cards.forEach(card => {
                card.classList.add('search-hidden');
                card.classList.remove('fade-out');
            });
            
            // Show only matching cards with staggered animation
            results.forEach((result, index) => {
                setTimeout(() => {
                    const matchingCard = this.findMatchingCard(result);
                    if (matchingCard) {
                        this.updateCardContent(matchingCard, result);
                        matchingCard.classList.add('search-match');
                        matchingCard.classList.remove('search-hidden', 'fade-out');
                        matchingCard.classList.add('fade-in');
                    }
                }, index * 50); // Stagger animation setiap 50ms - lebih cepat
            });
            
            // Remove no results if showing
            this.hideNoResults();
        }, 150);
    }
    
    findMatchingCard(result) {
        const cards = document.querySelectorAll(this.config.cardSelector);
        for (let card of cards) {
            const cardId = card.getAttribute('data-class-id');
            if (cardId == result.id) {
                return card;
            }
        }
        return null;
    }
    
    updateCardContent(card, result) {
        // Update card content with highlighted text
        const nameElement = card.querySelector('h3');
        const subjectElement = card.querySelector('.text-sm.text-gray-600');
        
        // For kelas (beranda-guru.php & kelas-beranda-user.php)
        if (result.namaKelas_highlighted && nameElement && !result.namaUjian_highlighted) {
            nameElement.innerHTML = result.namaKelas_highlighted;
        }
        
        // For ujian (ujian-guru.php & ujian-user.php) 
        if (result.namaUjian_highlighted && nameElement) {
            nameElement.innerHTML = result.namaUjian_highlighted;
        }
        
        // Update subject/class info
        if (subjectElement) {
            if (result.namaKelas_highlighted && result.namaUjian_highlighted) {
                // For ujian pages, show class name in subject position
                subjectElement.innerHTML = result.namaKelas_highlighted;
            } else if (result.mataPelajaran_highlighted) {
                subjectElement.innerHTML = result.mataPelajaran_highlighted;
            } else if (result.namaGuru_highlighted) {
                // For siswa class page, show teacher name in subject position
                subjectElement.innerHTML = result.namaGuru_highlighted;
            }
        }
    }
    
    restoreOriginalCards() {
        const cards = document.querySelectorAll(this.config.cardSelector);
        const resultsContainer = this.elements.resultsContainer;
        
        // Remove searching class
        if (resultsContainer) {
            resultsContainer.classList.remove('searching');
        }
        
        // Restore all cards with staggered animation
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.remove('fade-out', 'search-match', 'search-hidden');
                card.classList.add('fade-in');
                
                // Restore original content
                this.restoreCardContent(card);
            }, index * 25); // Lebih cepat untuk restore
        });
        
        // Hide no results
        this.hideNoResults();
    }
    
    restoreCardContent(card) {
        // Restore original content from stored data
        const cardId = card.getAttribute('data-class-id');
        const originalCard = this.originalCards.find(c => c.id == cardId);
        
        if (originalCard) {
            const nameElement = card.querySelector('h3');
            const subjectElement = card.querySelector('.text-sm.text-gray-600');
            
            if (nameElement) {
                // Restore name (could be namaKelas or namaUjian)
                nameElement.textContent = originalCard.namaKelas || originalCard.namaUjian || originalCard.name;
            }
            
            if (subjectElement) {
                // Restore subject (could be mataPelajaran, namaGuru, or class info)
                subjectElement.textContent = originalCard.mataPelajaran || originalCard.namaGuru || originalCard.subject || originalCard.kelas;
            }
        }
    }
    
    storeOriginalCards() {
        const cards = document.querySelectorAll(this.config.cardSelector);
        this.originalCards = [];
        
        cards.forEach(card => {
            const cardId = card.getAttribute('data-class-id');
            const nameElement = card.querySelector('h3');
            const subjectElement = card.querySelector('.text-sm.text-gray-600');
            
            if (cardId && nameElement) {
                const cardData = {
                    id: cardId,
                    name: nameElement.textContent.trim()
                };
                
                // Store different fields based on page type
                // For beranda-guru (kelas)
                cardData.namaKelas = nameElement.textContent.trim();
                
                // For ujian-guru (ujian)  
                cardData.namaUjian = nameElement.textContent.trim();
                
                // Store subject/secondary info
                if (subjectElement) {
                    cardData.subject = subjectElement.textContent.trim();
                    cardData.mataPelajaran = subjectElement.textContent.trim();
                    cardData.kelas = subjectElement.textContent.trim();
                    cardData.namaGuru = subjectElement.textContent.trim(); // For siswa page
                }
                
                this.originalCards.push(cardData);
            }
        });
    }
    
    showNoResults() {
        let noResultsElement = document.querySelector('.search-no-results');
        
        if (!noResultsElement) {
            noResultsElement = document.createElement('div');
            noResultsElement.className = 'search-no-results col-span-full';
            noResultsElement.innerHTML = `
                <div class="icon">
                    <i class="ti ti-search-off"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Tidak ada hasil</h3>
                <p class="text-sm">Tidak ditemukan kelas yang sesuai dengan pencarian "${this.currentQuery}"</p>
            `;
            
            const resultsContainer = this.elements.resultsContainer;
            if (resultsContainer) {
                resultsContainer.appendChild(noResultsElement);
            }
        }
        
        noResultsElement.querySelector('p').innerHTML = 
            `Tidak ditemukan kelas yang sesuai dengan pencarian "<strong>${this.currentQuery}</strong>"`;
        noResultsElement.classList.add('active');
    }
    
    hideNoResults() {
        const noResultsElement = document.querySelector('.search-no-results');
        if (noResultsElement) {
            noResultsElement.classList.remove('active');
            setTimeout(() => {
                if (noResultsElement.parentNode) {
                    noResultsElement.parentNode.removeChild(noResultsElement);
                }
            }, 400);
        }
    }
    
    // Public method to destroy the search system
    destroy() {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Remove event listeners and restore original state
        this.deactivateSearch();
    }
}

// Auto-initialize if configuration is found
document.addEventListener('DOMContentLoaded', function() {
    // Check if search configuration exists on the page
    const searchConfig = window.searchSystemConfig;
    if (searchConfig) {
        window.searchSystem = new SearchSystem(searchConfig);
    }
});