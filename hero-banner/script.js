document.addEventListener('DOMContentLoaded', function() {
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const heroCard = document.querySelector('.hero-card');
    
    // Array to hold image paths from hero_images folder
    // The script will attempt to load images from the hero_images folder
    let images = [];
    
    // Function to check if images exist in hero_images folder and populate the array
    async function loadImagesFromFolder() {
        // Define possible image names
        const possibleImages = [
            'hero1.jpg', 'hero2.jpg', 'hero3.jpg', 
            'hero1.png', 'hero2.png', 'hero3.png',
            'image1.jpg', 'image2.jpg', 'image3.jpg',
            'banner1.jpg', 'banner2.jpg', 'banner3.jpg'
        ];
        
        // Check which images exist in the folder
        for (const imageName of possibleImages) {
            try {
                const response = await fetch(`./../hero_images/${imageName}`);
                if (response.ok) {
                    images.push(`./../hero_images/${imageName}`);
                }
            } catch (error) {
                // Continue checking other possible images
                continue;
            }
        }
        
        // If no local images found, use fallback images
        if (images.length === 0) {
            console.warn("No images found in hero_images folder, using fallback images");
            images = [
                'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2377&q=80',
                'https://images.unsplash.com/photo-1483985988355-763728e1935b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2370&q=80',
                'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2365&q=80'
            ];
        }
        
        // Initialize the first image
        if (images.length > 0) {
            changeBackgroundImage(0);
        }
    }
    
    let currentImageIndex = 0;
    
    // Function to change the background image
    function changeBackgroundImage(index) {
        heroCard.style.backgroundImage = `url('${images[index]}')`;
        heroCard.style.backgroundSize = 'cover';
        heroCard.style.backgroundPosition = 'center';
    }
    
    // Next button functionality
    nextBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (images.length > 0) {
            currentImageIndex = (currentImageIndex + 1) % images.length;
            changeBackgroundImage(currentImageIndex);
        }
    });
    
    // Previous button functionality
    prevBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (images.length > 0) {
            currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
            changeBackgroundImage(currentImageIndex);
        }
    });
    
    // Optional: Auto-rotate the images every 5 seconds
    setInterval(() => {
        if (images.length > 0) {
            currentImageIndex = (currentImageIndex + 1) % images.length;
            changeBackgroundImage(currentImageIndex);
        }
    }, 5000);
    
    // Load images and initialize
    loadImagesFromFolder();
});