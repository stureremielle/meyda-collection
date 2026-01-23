<?php
/**
 * Newsletter Component
 * A bold, minimal sign-up section for the "Inner Circle".
 */
function renderNewsletter() {
    ob_start();
    ?>
    <section class="newsletter-section" id="newsletter">
        <div class="newsletter-container">
            <div class="newsletter-content">
                <h2 class="newsletter-title">Join The Inner Circle</h2>
                <p class="newsletter-subtitle">BE THE FIRST TO ACCESS OUR PRIVATE RELEASES AND EXCLUSIVE COLLECTIONS.</p>
                
                <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Subscribed to the Inner Circle!');">
                    <div class="input-group">
                        <input type="email" class="newsletter-input" placeholder="ENTER YOUR EMAIL" required>
                        <button type="submit" class="newsletter-submit">JOIN</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
?>
