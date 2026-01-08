<?php
// get_hero_images.php
header('Content-Type: application/json');

$imageDir = 'hero_images/';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$images = [];

if (is_dir($imageDir)) {
    $files = scandir($imageDir);
    
    foreach ($files as $file) {
        // Skip current and parent directory references
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        // Check if it's a file and has an allowed extension
        $pathInfo = pathinfo($file);
        if (isset($pathInfo['extension']) && 
            in_array(strtolower($pathInfo['extension']), $allowedExtensions)) {
            $images[] = $imageDir . $file;
        }
    }
}

// If no images found, return default image
if (empty($images)) {
    $images = ['assets/model.png'];
}

echo json_encode($images);
?>