# HeroCard Component

The HeroCard component is a comprehensive hero section component that includes all the elements specified in the requirements.

## Features

- Single bounded container with responsive design
- Width: 100% up to max-width of 1200px
- Horizontal padding: 2rem, Vertical padding: 3rem
- Border-radius: 8px
- Overflow: hidden to prevent content overflow
- Aspect ratio maintained at 16:9
- Responsive design that prevents horizontal scrollbars

## Child Elements

### HeroImage
- Full width inside card
- Maintains aspect ratio of 16:9
- Background image with cover property

### HeadlineText
- Top-aligned within content area
- Max-width: 600px
- Margin-bottom: 1.5rem

### ShopNowCTA
- Display: flex with flex-direction: row
- Align-items: center
- Button and arrow form a single unit

### DecorativeArrow
- Directly adjacent to the button's right edge
- Zero gap between button and arrow
- Same baseline alignment

### CarouselNavArrows
- Positioned inside the card (not at viewport edges)
- Inset from card sides by 32px (within 24px-40px range)
- Vertically centered on content

### SloganText
- Bottom-left aligned within card
- Aligned to left padding edge
- Positioned near bottom of card

## Usage

```php
<?php
require_once 'HeroCard.php';

// Render with default values
echo renderHeroCard();

// Or customize with options
echo renderHeroCard([
    'headline' => 'Your custom headline',
    'slogan' => 'YOUR CUSTOM SLOGAN',
    'cta_text' => 'Custom Button Text',
    'image_src' => 'path/to/your/image.jpg',
    'image_alt' => 'Descriptive alt text'
]);
?>
```

## Options

- `headline`: Text for the main headline (default: 'Discover our latest collection')
- `slogan`: Text for the bottom-left slogan (default: 'MAKE YOUR LOOK MORE SIGMA')
- `cta_text`: Text for the call-to-action button (default: 'Shop Now')
- `image_src`: Path to the background image (default: 'hero_images/1.jpg')
- `image_alt`: Alt text for the image (default: 'Hero background')

## Styling

The component uses CSS variables defined in the main stylesheet for consistent theming:
- `--card`: Background color
- `--md-sys-color-primary`: Primary accent color
- `--md-sys-color-on-primary`: Text on primary color
- `--md-sys-color-on-surface`: Surface text color