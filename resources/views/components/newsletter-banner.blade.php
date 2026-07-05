{{-- Presentational only, no backend — Phase 13.1 scope explicitly excludes a
     real mailing-list/subscription system. Submitting shows a client-side
     thank-you message instead of posting anywhere. --}}
<section class="newsletter-banner">
    <h2 class="newsletter-banner-title">Stay In Style</h2>
    <p class="newsletter-banner-subtitle">Sign up for new arrivals, trends, and exclusive offers.</p>

    <form class="newsletter-banner-form" data-newsletter-form onsubmit="return false;">
        <label for="newsletter-email" class="visually-hidden">Email Address</label>
        <input type="email" id="newsletter-email" placeholder="Enter your email" class="input-field-input" required>
        <x-button type="submit" variant="primary">Subscribe</x-button>
    </form>

    <p class="newsletter-banner-note" data-newsletter-note hidden>Thanks for signing up!</p>
</section>
