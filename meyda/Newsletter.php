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
                
                <form class="newsletter-form" id="newsletterForm">
                    <div class="input-group">
                        <input type="email" name="email" class="newsletter-input" placeholder="ENTER YOUR EMAIL" required>
                        <button type="submit" class="newsletter-submit">JOIN</button>
                    </div>
                </form>

                <script>
                document.getElementById('newsletterForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const form = this;
                    const email = form.querySelector('input[name=\"email\"]').value;
                    const btn = form.querySelector('.newsletter-submit');
                    const originalText = btn.textContent;
                    
                    btn.textContent = '...';
                    btn.disabled = true;

                    const formData = new FormData(form);
                    
                    fetch('newsletter_process.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof showNotification === 'function') {
                                showNotification(data.message, 'success');
                            } else {
                                alert(data.message);
                            }
                            form.reset();
                        } else {
                            if (typeof showNotification === 'function') {
                                showNotification(data.error, 'error');
                            } else {
                                alert(data.error);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (typeof showNotification === 'function') {
                            showNotification('An error occurred. Please try again.', 'error');
                        }
                    })
                    .finally(() => {
                        btn.textContent = originalText;
                        btn.disabled = false;
                    });
                });
                </script>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
?>
