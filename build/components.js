// Component Loader for Teas & C's Website
class ComponentLoader {
    constructor() {
        this.components = new Map();
        this.init();
    }

    init() {
        this.loadAndInsertFooter();
    }

    async loadAndInsertFooter() {
        try {
            // Load footer component
            const footerResponse = await fetch('footer.html');
            if (footerResponse.ok) {
                const footerHTML = await footerResponse.text();
                const placeholder = document.querySelector('.footer-placeholder');
                if (placeholder) {
                    placeholder.innerHTML = footerHTML;
                    this.updateCopyrightYear();
                } else {
                    // If placeholder not found yet, wait for DOM to load
                    document.addEventListener('DOMContentLoaded', () => {
                        const placeholderLater = document.querySelector('.footer-placeholder');
                        if (placeholderLater) {
                            placeholderLater.innerHTML = footerHTML;
                            this.updateCopyrightYear();
                        }
                    });
                }
            }
        } catch (error) {
            console.warn('Could not load footer:', error);
        }
    }

    updateCopyrightYear() {
        // Set copyright year to current year
        const yearElement = document.getElementById('copyright-year');
        if (yearElement) {
            yearElement.textContent = new Date().getFullYear();
        }
    }
}

// Initialize component loader
const componentLoader = new ComponentLoader();

// Export for use in other scripts
window.ComponentLoader = ComponentLoader;
