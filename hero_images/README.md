# Hero Images Folder

This folder is designated for images that will be used in the hero section carousel. To use your own images:

1. Place your images in this folder (supported formats: JPG, PNG, WEBP, etc.)
2. Update the `images` array in `/workspace/hero-banner/script.js` to reference your images like:
   ```javascript
   const images = [
       'hero_images/your-image1.jpg',
       'hero_images/your-image2.jpg',
       'hero_images/your-image3.jpg'
   ];
   ```

Make sure your images are optimized for web use and have appropriate dimensions to fill the 16:9 aspect ratio container without distortion.