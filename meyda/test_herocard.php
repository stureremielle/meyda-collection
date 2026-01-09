<?php
// Simple test page for HeroCard component
require_once __DIR__ . '/HeroCard.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>HeroCard Component Test</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <main class="container">
    <h1>HeroCard Component Test</h1>
    
    <?php
    // Test the HeroCard with default values
    echo renderHeroCard();
    ?>
    
    <div style="height: 100vh; background: var(--md-sys-color-surface); display: flex; align-items: center; justify-content: center;">
      <p>Scroll down to see the HeroCard component in action</p>
    </div>
    
    <div id="products" style="height: 100vh; background: var(--md-sys-color-surface-variant); display: flex; align-items: center; justify-content: center;">
      <p>This is the products section (target for CTA buttons)</p>
    </div>
  </main>
</body>
</html>