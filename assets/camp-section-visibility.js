/**
 * Camp Section Visibility Handler
 * Hides section titles and dividers when their corresponding shortcode content is empty
 */
(function() {
    'use strict';

    /**
     * Check if an element exists and has visible content
     */
    function hasContent(selector) {
        const element = document.querySelector(selector);
        if (!element) {
            return false;
        }
        // Check if element has any text content or child elements
        return element.textContent.trim().length > 0 || element.children.length > 0;
    }

    /**
     * Hide elements by class name
     */
    function hideElements(classNames) {
        classNames.forEach(className => {
            const elements = document.querySelectorAll('.' + className);
            elements.forEach(element => {
                element.style.display = 'none';
            });
        });
    }

    /**
     * Initialize section visibility on page load
     */
    function initSectionVisibility() {
        // Check Sessions section
        if (!hasContent('.camp-sessions')) {
            hideElements(['sessions-title-hide', 'sessions-divider-hide']);
        }

        // Check Cabins/Accommodations section
        if (!hasContent('.camp-accommodations')) {
            hideElements(['cabins-title-hide', 'cabins-divider-hide']);
        }

        // Check FAQs section
        if (!hasContent('.camp-faqs')) {
            hideElements(['faqs-title-hide', 'faqs-divider-hide']);
        }
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSectionVisibility);
    } else {
        // DOM already loaded
        initSectionVisibility();
    }

    // Also run after Elementor frontend init (if Elementor is present)
    if (typeof elementorFrontend !== 'undefined') {
        elementorFrontend.hooks.addAction('frontend/element_ready/widget', initSectionVisibility);
    }
})();
