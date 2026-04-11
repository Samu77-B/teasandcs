// Component Loader for Teas & C's Website
class ComponentLoader {
    constructor() {
        this.components = new Map();
        this.init();
    }

    init() {
        this.loadAndInsertFooter();
        this.createSocialMediaButton();
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

    createSocialMediaButton() {
        const socialHTML = `
            <div class="social-sticky">
                <div class="social-expand" id="socialExpand">
                    <a href="https://www.tiktok.com/@teas_and_cs" target="_blank" rel="noopener noreferrer" class="social-item" title="Follow us on TikTok">
                        <img src="icons/tiktokWht.png" alt="TikTok" class="social-item-icon-img">
                    </a>
                    <a href="https://www.instagram.com/teasandcs" target="_blank" rel="noopener noreferrer" class="social-item" title="Follow us on Instagram">
                        <img src="icons/instaWht.png" alt="Instagram" class="social-item-icon-img">
                    </a>
                </div>
                <button class="social-button" id="socialButton" aria-label="Toggle social media links">
                    <img src="icons/socialMedia.png" alt="Social Media" class="social-icon-img">
                </button>
            </div>
        `;

        // Wait for DOM to be ready
        if (document.body) {
            document.body.insertAdjacentHTML('beforeend', socialHTML);
            this.initSocialButton();
        } else {
            document.addEventListener('DOMContentLoaded', () => {
                document.body.insertAdjacentHTML('beforeend', socialHTML);
                this.initSocialButton();
            });
        }
    }

    initSocialButton() {
        const socialButton = document.getElementById('socialButton');
        const socialExpand = document.getElementById('socialExpand');

        if (socialButton && socialExpand) {
            socialButton.addEventListener('click', () => {
                socialExpand.classList.toggle('active');
            });

            // Close when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.social-sticky')) {
                    socialExpand.classList.remove('active');
                }
            });
        }
    }
}

// Initialize component loader
const componentLoader = new ComponentLoader();

// Export for use in other scripts
window.ComponentLoader = ComponentLoader;
