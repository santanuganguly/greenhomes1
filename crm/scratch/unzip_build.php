<?php
set_time_limit(0);
ini_set('memory_limit', '512M');
$zip = new ZipArchive;
$res = $zip->open('build.zip');
if ($res === TRUE) {
    $zip->extractTo('./');
    $zip->close();
    echo "extracted successfully";
} else {
    echo "failed to extract. code: " . $res;
}
?>
