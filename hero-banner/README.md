# Hero Banner Component

A responsive hero banner component with carousel functionality, designed according to your specifications.

## Features

- Full-width container with rounded corners
- Background image of a person in a hoodie
- Text overlay with tagline in top-left and main text at bottom-center
- Carousel navigation controls on the right side
- "Start shopping" CTA button at bottom-right with arrow icon
- Fully responsive design
- Auto-rotating images (every 5 seconds)

## Files Included

- `index.html` - The main HTML structure
- `styles.css` - Styling for the component
- `script.js` - JavaScript functionality for the carousel
- `assets/` - Folder for your custom images
- `README.md` - This documentation
- `package.json` - Project configuration file

## How to Use

1. Replace the placeholder image URLs in `styles.css` and `script.js` with your own images
2. Customize the text content in `index.html` as needed
3. Adjust colors, sizes, and other styling in `styles.css`
4. Modify the carousel behavior in `script.js` if needed

## Customization

### Images
To use your own images:
1. In `styles.css`, replace the URL in the `.banner-container` background-image property
2. In `script.js`, update the `images` array with your own image URLs

### Colors and Styling
All styling is in `styles.css`. You can easily modify:
- Background overlay opacity (adjust the rgba value in `.banner-container::before`)
- Text colors, sizes, and positioning
- Button styles and hover effects
- Border radius of the container

### Carousel Behavior
In `script.js` you can:
- Adjust the auto-rotation interval (currently 5 seconds)
- Modify the navigation behavior
- Add more images to the carousel

## Responsive Design

The component is fully responsive and adapts to different screen sizes:
- Desktop: Full feature set
- Tablet: Adjusted sizes and spacing
- Mobile: Stacked layout for better readability

## Dependencies

- Font Awesome (loaded via CDN) for icons
- No other external dependencies

## License

This component is free to use and modify for personal and commercial projects.