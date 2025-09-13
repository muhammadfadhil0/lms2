/**
 * Notification Highlight Script
 * Handles auto-scroll and highlight when redirected from notifications
 */

// Flag to prevent double execution
let highlightExecuted = false;

// Check if page was loaded from notification redirect and handle highlighting
function handleNotificationHighlight() {
    // Prevent double execution
    if (highlightExecuted) {
        console.log('🎯 Highlight already executed, skipping');
        return;
    }
    
    // Get the hash from URL
    const hash = window.location.hash;
    
    if (hash) {
        console.log('🎯 Notification redirect detected, hash:', hash);
        
        // Mark as executed to prevent double run
        highlightExecuted = true;
        
        // Wait a bit for page to fully load
        setTimeout(() => {
            const targetElement = document.querySelector(hash);
            
            if (targetElement) {
                // Scroll to target element
                scrollToElement(targetElement);
                
                // Highlight the element
                highlightElement(targetElement);
                
                console.log('✨ Element highlighted:', hash);
            } else {
                console.log('❌ Target element not found:', hash);
                
                // Try alternative selectors based on hash pattern
                tryAlternativeSelectors(hash);
                
                // If still not found after alternatives, reset flag
                if (!document.querySelector(hash)) {
                    highlightExecuted = false;
                }
            }
        }, 1000);
    }
}

// Scroll to element smoothly
function scrollToElement(element) {
    const offsetTop = element.offsetTop - 100; // 100px offset from top
    
    window.scrollTo({
        top: offsetTop,
        behavior: 'smooth'
    });
}

// Highlight element with orange border for 2 seconds
function highlightElement(element) {
    // Store original styles
    const originalBorder = element.style.border;
    const originalBoxShadow = element.style.boxShadow;
    const originalTransition = element.style.transition;
    const originalBackground = element.style.backgroundColor;
    
    // Add highlight styles
    element.style.transition = 'all 0.3s ease';
    element.style.border = '3px solid #f97316'; // Orange-500
    element.style.boxShadow = '0 0 20px rgba(249, 115, 22, 0.3)';
    element.style.backgroundColor = 'rgba(249, 115, 22, 0.1)';
    
    // Remove highlight after 2 seconds
    setTimeout(() => {
        element.style.transition = 'all 0.5s ease';
        
        // Force remove all highlight styles
        element.style.removeProperty('border');
        element.style.removeProperty('box-shadow');
        element.style.removeProperty('background-color');
        
        // Also try setting to original values as backup
        element.style.border = originalBorder || '';
        element.style.boxShadow = originalBoxShadow || '';
        element.style.backgroundColor = originalBackground || '';
        
        // Restore original transition after animation
        setTimeout(() => {
            element.style.removeProperty('transition');
            element.style.transition = originalTransition || '';
        }, 500);
    }, 2000); // Changed from 3000ms to 2000ms
}

// Try alternative selectors if main hash fails
function tryAlternativeSelectors(hash) {
    const hashValue = hash.substring(1); // Remove #
    
    // Try data attributes
    let element = document.querySelector(`[data-id="${hashValue}"]`);
    if (!element) {
        element = document.querySelector(`[data-post-id="${hashValue}"]`);
    }
    if (!element) {
        element = document.querySelector(`[data-assignment-id="${hashValue}"]`);
    }
    if (!element) {
        element = document.querySelector(`[data-ujian-id="${hashValue}"]`);
    }
    
    // Try class-based selectors
    if (!element && hashValue.includes('post-')) {
        const postId = hashValue.replace('post-', '');
        element = document.querySelector(`.post-item[data-id="${postId}"]`);
    }
    
    if (!element && hashValue.includes('assignment-')) {
        const assignmentId = hashValue.replace('assignment-', '');
        element = document.querySelector(`.assignment-item[data-id="${assignmentId}"]`);
    }
    
    if (element) {
        console.log('✅ Found element with alternative selector');
        scrollToElement(element);
        highlightElement(element);
        
        // Update hash to match found element
        if (element.id) {
            window.history.replaceState(null, null, '#' + element.id);
        }
    } else {
        console.log('❌ No alternative selectors found for:', hashValue);
        // Reset flag since we couldn't find the element
        highlightExecuted = false;
    }
}

// Pulse animation for extra attention
function addPulseAnimation(element, duration = 2000) {
    element.style.animation = `pulse 1s ease-in-out infinite`;
    
    // Add CSS for pulse animation if not exists
    if (!document.querySelector('#pulse-keyframes')) {
        const style = document.createElement('style');
        style.id = 'pulse-keyframes';
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    }
    
    setTimeout(() => {
        element.style.animation = '';
    }, duration);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 NotificationHighlight: DOMContentLoaded fired');
    console.log('🎯 NotificationHighlight: Current URL:', window.location.href);
    console.log('🎯 NotificationHighlight: Current hash:', window.location.hash);
    handleNotificationHighlight();
});

// Also handle when hash changes (for SPA-like behavior)
window.addEventListener('hashchange', function() {
    handleNotificationHighlight();
});

// Export functions for manual use
window.NotificationHighlight = {
    handleNotificationHighlight,
    scrollToElement,
    highlightElement,
    addPulseAnimation,
    get highlightExecuted() { return highlightExecuted; },
    set highlightExecuted(value) { highlightExecuted = value; }
};