
    // Default language (English)
    let currentLang = 'en'; 

    function applyLanguage() {
        document.querySelectorAll('[data-en]').forEach(el => {
            el.innerText = el.getAttribute(`data-${currentLang}`);
        });
    }

    function toggleLanguage() {
        currentLang = currentLang === 'en' ? 'te' : 'en';
        applyLanguage();
    }

    // Apply default language (English) when the page loads
    document.addEventListener('DOMContentLoaded', () => {
        currentLang = 'en'; // Reset to default language on page load
        applyLanguage();
    });



    
  document.addEventListener("DOMContentLoaded", function () {
    function updateParallaxImage() {
        let parallaxElement = document.getElementById("intro"); // Select the element

        if (window.innerWidth <= 768) {
            parallaxElement.setAttribute("data-image-src", "img/RAJ_79499.jpg"); // Mobile image
        } else {
            parallaxElement.setAttribute("data-image-src", "img/rajuu.jpg"); // Desktop image
        }

        // Reinitialize the parallax effect if needed (for libraries like parallax.js)
        if (typeof jQuery !== "undefined" && typeof jQuery.fn.parallax !== "undefined") {
            jQuery(parallaxElement).parallax("destroy"); // Destroy previous instance
            jQuery(parallaxElement).parallax(); // Reinitialize with new image
        }
    }

    updateParallaxImage(); // Run once on page load
    window.addEventListener("resize", updateParallaxImage); // Update on resize
});



