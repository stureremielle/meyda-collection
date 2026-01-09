<?php
// Simple test file to check hero section rendering
require_once __DIR__ . '/HeroCard.php';

// Start a dummy session to prevent errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set some dummy session variables
$_SESSION['cart'] = [];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Hero Section Test</title>
    <link rel=\"stylesheet\" href=\"styles.css\">
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden; /* To test the 100vh behavior */
        }
    </style>
</head>
<body>";

echo renderHeroCard([
    'headline' => 'Discover our latest collection of premium fashion items designed to elevate your style.',
    'slogan' => 'MAKE YOUR LOOK MORE SIGMA',
    'cta_text' => 'Shop Now'
]);

echo "</body>
</html>";
?>