export function initProductGallery() {
    document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
        const mainImage = gallery.querySelector('[data-gallery-main]');
        const thumbs = gallery.querySelectorAll('[data-gallery-thumb]');

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                if (mainImage.tagName === 'IMG') {
                    mainImage.src = thumb.dataset.imageUrl;
                }

                thumbs.forEach((t) => t.classList.remove('is-active'));
                thumb.classList.add('is-active');
            });
        });
    });
}
