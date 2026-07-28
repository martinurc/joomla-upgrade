(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const elements = document.querySelectorAll(".sppb-dynamic-content-text");
        elements.forEach(element => {
            const fullTextTemplate = element.querySelector(".sppb-addon-content-full-text");
            const showMoreButtonElement = element?.parentElement?.querySelector(".sppb-btn-container")?.querySelector(".sppb-btn-show-more");
            
            if (fullTextTemplate && showMoreButtonElement) {
                const handleClick = () => {
                    const fullTextContent = fullTextTemplate.innerHTML;
                    // Remove the template and button container
                    fullTextTemplate.remove();
                    showMoreButtonElement.closest('.sppb-btn-container').remove();
                    // Insert the full text content
                    element.innerHTML = fullTextContent;
                    showMoreButtonElement.removeEventListener('click', handleClick);
                };
                showMoreButtonElement.addEventListener('click', handleClick);
            }
        });
    })
})();
