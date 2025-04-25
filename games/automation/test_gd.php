<?php
// Path to the image you want to use
$imagePath = 'badge-template.png'; // Change to your image file

// Create image from file
$image = imagecreatefrompng($imagePath);

// Set the text you want to add
$title = "Office Network"; //Challenge Name
$fontPath = 'fonts/Calistoga.ttf'; 
// Set font size
$fontSize = 20;
$y = 300; 
$textColor = imagecolorallocate($image, 255, 255, 255); 

$bbox = imagettfbbox($fontSize, 0, $fontPath, $title);
$textWidth = $bbox[2] - $bbox[0];
$boxLeft = 105;
$boxRight = 395;
$boxWidth = $boxRight - $boxLeft;
$x = $boxLeft + ($boxWidth - $textWidth) / 2;


// Add Text to the image
imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $title);

// Set the text you want to add
$type = 'Networking'; //Challenge Type
// Set font size
$fontSize = 14.5; 

// Set text position (X, Y)
$y = 340; 

$bbox = imagettfbbox($fontSize, 0, $fontPath, $type);
$textWidth = $bbox[2] - $bbox[0];
$boxLeft = 192;
$boxRight = 305;
$boxWidth = $boxRight - $boxLeft;
$x = $boxLeft + ($boxWidth - $textWidth) / 2;

$textColor = imagecolorallocate($image, 255, 255, 255); 

// Add Text to the image
imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $type);



// Output the image to the browser
header('Content-Type: image/jpeg');
imagejpeg($image);

// Free up memory
imagedestroy($image);
?>