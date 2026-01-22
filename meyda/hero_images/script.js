document.addEventListener("DOMContentLoaded", function () {
  const prevBtn = document.querySelector(".prev-btn");
  const nextBtn = document.querySelector(".next-btn");
  const bannerContainer = document.querySelector(".banner-container");

  // Sample images for the carousel (these would be replaced with images from hero_images folder)
  const images = [
    "hero_images/1.jpg",
    "hero_images/2.jpg",
    "hero_images/3.jpg",
  ];

  let currentImageIndex = 0;

  // Function to change the background image
  function changeBackgroundImage(index) {
    bannerContainer.style.backgroundImage = `url('${images[index]}')`;
    bannerContainer.style.backgroundSize = "cover";
    bannerContainer.style.backgroundPosition = "center";
  }

  // Next button functionality
  nextBtn.addEventListener("click", function (e) {
    e.preventDefault();
    currentImageIndex = (currentImageIndex + 1) % images.length;
    changeBackgroundImage(currentImageIndex);
  });

  // Previous button functionality
  prevBtn.addEventListener("click", function (e) {
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
