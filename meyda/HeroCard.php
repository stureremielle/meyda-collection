<?php
/**
 * HeroCard Component
 * A single bounded container with all specified elements
 */
function renderHeroCard($options = []) {
    // Default values
    $defaults = [
        'headline' => 'Discover our latest collection',
        'slogan' => 'MAKE YOUR LOOK MORE SIGMA',
        'cta_text' => 'Shop Now',
        'image_src' => 'hero_images/1.jpg',
        'image_alt' => 'Hero background',
        'max_width' => '1200px'
    ];
    
    $options = array_merge($defaults, $options);
    
    $carousel_images = [
        'hero_images/1.jpg',
        'hero_images/2.jpg', 
        'hero_images/3.jpg'
    ];
    
    ob_start();
    ?>
    
    <section class="hero-card-wrapper">
        <div class="hero-card">
            <!-- Hero Image -->
            <div class="hero-image-container">
                <img 
                    src="<?php echo htmlspecialchars($options['image_src']); ?>" 
                    alt="<?php echo htmlspecialchars($options['image_alt']); ?>"
                    class="hero-image"
                    id="heroMainImage"
                >
            </div>
            
            <!-- Inner container with padding -->
            <div class="hero-card-inner">
                <!-- Content overlay -->
                <div class="hero-content-overlay">
                    <!-- Headline Text -->
                    <div class="headline-text">
                        <?php echo htmlspecialchars($options['headline']); ?>
                    </div>
                    
                    <!-- CTA Button with Direct Adjacent Arrow -->
                    <div class="cta-container">
                        <a href="#products" class="shop-now-button">
                            <?php echo htmlspecialchars($options['cta_text']); ?>
                        </a>
                        <a href="#products" class="decorative-arrow" aria-label="Continue">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </div>
                    
                    <!-- Slogan Text (bottom-left aligned) -->
                    <div class="slogan-text">
                        <?php echo htmlspecialchars($options['slogan']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Carousel Navigation Arrows (inside card) -->
            <button class="carousel-nav prev" aria-label="Previous image">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <button class="carousel-nav next" aria-label="Next image">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </section>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Carousel functionality
            let currentImageIndex = 0;
            const images = <?php echo json_encode($carousel_images); ?>;
            
            const prevBtn = document.querySelector('.carousel-nav.prev');
            const nextBtn = document.querySelector('.carousel-nav.next');
            const heroImage = document.getElementById('heroMainImage');
            
            function updateImage(index) {
                heroImage.src = images[index];
                heroImage.alt = `Hero background ${index + 1}`;
            }
            
            prevBtn.addEventListener('click', function() {
                currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
                updateImage(currentImageIndex);
            });
            
            nextBtn.addEventListener('click', function() {
                currentImageIndex = (currentImageIndex + 1) % images.length;
                updateImage(currentImageIndex);
            });
            
            // Smooth scroll for CTA buttons
            const ctaButtons = document.querySelectorAll('.shop-now-button, .decorative-arrow');
            ctaButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetElement = document.querySelector('#products');
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
    
    <?php
    return ob_get_clean();
}
?>