document.addEventListener('DOMContentLoaded', function() {
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const bannerContainer = document.querySelector('.banner-container');
    
    // Sample images for the carousel (these would be replaced with images from hero_images folder)
    const images = [
        'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2377&q=80',
        'https://images.unsplash.com/photo-1483985988355-763728e1935b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2370&q=80',
        'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2365&q=80'
    ];
    
    let currentImageIndex = 0;
    
    // Function to change the background image
    function changeBackgroundImage(index) {
        bannerContainer.style.backgroundImage = `url('${images[index]}')`;
        bannerContainer.style.backgroundSize = 'cover';
        bannerContainer.style.backgroundPosition = 'center';
    }
    
    // Next button functionality
    nextBtn.addEventListener('click', function(e) {
        e.preventDefault();
        currentImageIndex = (currentImageIndex + 1) % images.length;
        changeBackgroundImage(currentImageIndex);
    });
    
    // Previous button functionality
    prevBtn.addEventListener('click', function(e) {
        e.preventDefault();
        currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
        changeBackgroundImage(currentImageIndex);
    });
    
    // Optional: Auto-rotate the images every 5 seconds
    setInterval(() => {
        currentImageIndex = (currentImageIndex + 1) % images.length;
        changeBackgroundImage(currentImageIndex);
    }, 5000);
    
    // Initialize the first image
    changeBackgroundImage(currentImageIndex);
});