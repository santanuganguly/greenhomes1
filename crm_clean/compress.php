<?php
$src = 'C:/Users/91963/Downloads/+91 90466 65256 greenhillgroup.in Sevoke Road, Siliguri (1) (1).png';
$dest = 'C:/Users/91963/Downloads/welcome-banner-compressed.jpg';
$img = imagecreatefrompng($src);
imagejpeg($img, $dest, 85);
imagedestroy($img);
echo "Done";
