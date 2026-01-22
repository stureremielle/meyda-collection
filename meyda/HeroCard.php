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
        asset('hero_images/1.jpg'),
        asset('hero_images/2.jpg'), 
        asset('hero_images/3.jpg')
    ];
    
    ob_start();
    ?>
    
    <section class="hero-card">
        <!-- Hero Carousel Track -->
        <div class="hero-carousel-container">
            <div class="hero-carousel-track" id="heroTrack">
                <?php foreach ($carousel_images as $index => $img_src): ?>
                    <div class="hero-slide">
                        <img 
                            src="<?php echo htmlspecialchars($img_src); ?>" 
                            alt="Hero background <?php echo $index + 1; ?>"
                            class="hero-image"
                        >
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Headline Text - Top Left -->
        <div class="headline-text">
            <?php echo htmlspecialchars($options['headline']); ?>
        </div>
        
        <!-- Slogan Text - Bottom Left -->
        <div class="slogan-text">
            <?php echo htmlspecialchars($options['slogan']); ?>
        </div>
        
        <!-- Carousel Navigation Arrows - Top Right -->
        <div class="carousel-arrows-container">
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
        
        <!-- CTA Button - Bottom Right -->
        <a href="#products" class="shop-now-button">
            <?php echo htmlspecialchars($options['cta_text']); ?>
        </a>
    </section>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentImageIndex = 0;
            const track = document.getElementById('heroTrack');
            const slides = document.querySelectorAll('.hero-slide');
            const totalSlides = slides.length;
            
            const prevBtn = document.querySelector('.carousel-nav.prev');
            const nextBtn = document.querySelector('.carousel-nav.next');
            
            let autoCycleInterval;

            function updateCarousel() {
                const offset = -currentImageIndex * 100;
                track.style.transform = `translateX(${offset}%)`;
            }

            function nextSlide() {
                currentImageIndex = (currentImageIndex + 1) % totalSlides;
                updateCarousel();
            }

            function prevSlide() {
                currentImageIndex = (currentImageIndex - 1 + totalSlides) % totalSlides;
                updateCarousel();
            }

            function startAutoCycle() {
                stopAutoCycle();
                autoCycleInterval = setInterval(nextSlide, 10000);
            }

            function stopAutoCycle() {
                if (autoCycleInterval) clearInterval(autoCycleInterval);
            }
            
            prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                prevSlide();
                startAutoCycle(); // Reset timer
            });
            
            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                nextSlide();
                startAutoCycle(); // Reset timer
            });

            // Handle touch events for mobile
            let touchStartX = 0;
            let touchEndX = 0;

            track.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
                stopAutoCycle();
            }, {passive: true});

            track.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                if (touchStartX - touchEndX > 50) nextSlide();
                if (touchEndX - touchStartX > 50) prevSlide();
                startAutoCycle();
            }, {passive: true});
            
            // Initial start
            startAutoCycle();

            // Smooth scroll for CTA button
            const ctaButton = document.querySelector('.shop-now-button');
            ctaButton.addEventListener('click', function(e) {
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
    </script>
    
    <?php
    return ob_get_clean();
}
?>