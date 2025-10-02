// Dark Mode Cleanup Script - Run this once to clean up completely
(function() {
    console.log('🧹 Running dark mode cleanup...');
    
    // 1. Clean localStorage completely
    const darkModeKeys = [
        'theme', 'theme-preference', 'user_theme', 'userTheme', 
        'darkMode', 'dark-mode', 'themePreference', 'appearance-theme',
        'ui-theme', 'color-scheme', 'mode'
    ];
    
    let cleaned = 0;
    darkModeKeys.forEach(key => {
        if (localStorage.getItem(key) !== null) {
            localStorage.removeItem(key);
            cleaned++;
            console.log(`🗑️ Removed localStorage: ${key}`);
        }
    });
    
    // 2. Clean sessionStorage
    darkModeKeys.forEach(key => {
        if (sessionStorage.getItem(key) !== null) {
            sessionStorage.removeItem(key);
            cleaned++;
            console.log(`🗑️ Removed sessionStorage: ${key}`);
        }
    });
    
    // 3. Remove dark classes from DOM
    const darkClasses = ['theme-dark', 'dark', 'dark-mode', 'dark-theme'];
    const elementsToClean = [document.documentElement, document.body];
    
    elementsToClean.forEach(element => {
        if (element) {
            darkClasses.forEach(cls => {
                if (element.classList.contains(cls)) {
                    element.classList.remove(cls);
                    cleaned++;
                    console.log(`🗑️ Removed class: ${cls} from ${element.tagName}`);
                }
            });
        }
    });
    
    // 4. Remove dark attributes
    const darkAttributes = ['data-theme', 'data-color-scheme', 'data-mode'];
    darkAttributes.forEach(attr => {
        if (document.documentElement.hasAttribute(attr)) {
            document.documentElement.removeAttribute(attr);
            cleaned++;
            console.log(`🗑️ Removed attribute: ${attr}`);
        }
    });
    
    // 5. Force light theme
    document.documentElement.classList.add('theme-light');
    document.documentElement.style.colorScheme = 'light';
    document.documentElement.style.backgroundColor = '#ffffff';
    document.documentElement.style.color = '#000000';
    
    // 6. Remove any inline dark styles
    const allElements = document.querySelectorAll('*[style]');
    allElements.forEach(element => {
        const style = element.style;
        if (style.backgroundColor && (
            style.backgroundColor.includes('#1a1a1a') || 
            style.backgroundColor.includes('#2d2d2d') ||
            style.backgroundColor.includes('rgb(26, 26, 26)') ||
            style.backgroundColor.includes('rgb(45, 45, 45)')
        )) {
            style.backgroundColor = '';
            cleaned++;
            console.log(`🗑️ Removed dark background from element`);
        }
        if (style.color && style.color.includes('#ffffff')) {
            style.color = '';
            cleaned++;
            console.log(`🗑️ Removed forced white text color`);
        }
    });
    
    console.log(`✅ Dark mode cleanup completed! Cleaned ${cleaned} items.`);
    console.log('🔆 Light mode is now forced and stable.');
    
    // 7. Create a persistent watcher to prevent dark mode from coming back
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes') {
                const target = mutation.target;
                const attrName = mutation.attributeName;
                
                // If someone tries to add dark mode attributes, remove them
                if (attrName === 'class' && target === document.documentElement) {
                    darkClasses.forEach(cls => {
                        if (target.classList.contains(cls)) {
                            target.classList.remove(cls);
                            console.log(`🚫 Prevented dark class: ${cls}`);
                        }
                    });
                }
                
                if (darkAttributes.includes(attrName) && target === document.documentElement) {
                    target.removeAttribute(attrName);
                    console.log(`🚫 Prevented dark attribute: ${attrName}`);
                }
            }
        });
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class', 'data-theme', 'data-color-scheme', 'data-mode', 'style']
    });
    
    console.log('👁️ Dark mode prevention watcher activated');
    
    return {
        cleaned: cleaned,
        message: 'Dark mode completely removed and prevented from returning'
    };
})();