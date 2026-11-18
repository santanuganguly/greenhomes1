<?php
ini_set('memory_limit', '512M');
$sourcePath = 'C:\Users\91963\Downloads\Vishwakarma Puja Invitation (3).png';
$destPath = 'C:\Users\91963\Downloads\Vishwakarma_Puja_Invitation_compressed.jpg';

// Load the PNG image
$image = imagecreatefrompng($sourcePath);

// Create a new image with white background (in case of transparency)
$bg = imagecreatetruecolor(imagesx($image), imagesy($image));
imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

// Save as JPEG with 80% quality to reduce size
imagejpeg($bg, $destPath, 80);

imagedestroy($image);
imagedestroy($bg);

echo "Compressed image saved to: " . $destPath . "\n";
echo "New size: " . (filesize($destPath) / 1024 / 1024) . " MB\n";
