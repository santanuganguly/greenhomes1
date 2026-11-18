Add-Type -AssemblyName System.Drawing

$logoPath = "C:\Users\91963\OneDrive\Documents\claudeee\green-homes\mobile-app\green-hills-logo.png"
$resDir   = "C:\Users\91963\OneDrive\Documents\claudeee\green-homes\mobile-app\android\app\src\main\res"

$srcImg = [System.Drawing.Image]::FromFile($logoPath)
$srcW = $srcImg.Width
$srcH = $srcImg.Height

$brandGreen = [System.Drawing.Color]::FromArgb(255, 33, 150, 83)

function Create-ForegroundIcon {
    param($size, $outputPath)
    $bmp = New-Object System.Drawing.Bitmap($size, $size, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.Clear([System.Drawing.Color]::FromArgb(0, 0, 0, 0))
    $margin = [int]($size * 0.08)
    $logoW = $size - (2 * $margin)
    $logoH = [int]($logoW * $srcH / $srcW)
    $logox = $margin
    $logoy = ($size - $logoH) / 2
    $g.DrawImage($srcImg, $logox, $logoy, $logoW, $logoH)
    $bmp.Save($outputPath, [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose(); $bmp.Dispose()
}

function Create-LauncherIcon {
    param($size, $outputPath)
    $bmp = New-Object System.Drawing.Bitmap($size, $size, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $brush = New-Object System.Drawing.SolidBrush($brandGreen)
    $g.FillRectangle($brush, 0, 0, $size, $size)
    $margin = [int]($size * 0.08)
    $logoW = $size - (2 * $margin)
    $logoH = [int]($logoW * $srcH / $srcW)
    $logox = $margin
    $logoy = ($size - $logoH) / 2
    $g.DrawImage($srcImg, $logox, $logoy, $logoW, $logoH)
    $bmp.Save($outputPath, [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose(); $bmp.Dispose()
}

# Foreground icons (transparent bg for adaptive icons)
$fgSizes = @{ "mipmap-mdpi"=108; "mipmap-hdpi"=162; "mipmap-xhdpi"=216; "mipmap-xxhdpi"=324; "mipmap-xxxhdpi"=432 }
Write-Host "Foreground icons..."
foreach ($f in $fgSizes.Keys) { Create-ForegroundIcon $fgSizes[$f] "$resDir\$f\ic_launcher_foreground.png"; Write-Host "  $f done" }

# Launcher icons (green bg)
$sizes = @{ "mipmap-mdpi"=48; "mipmap-hdpi"=72; "mipmap-xhdpi"=96; "mipmap-xxhdpi"=144; "mipmap-xxxhdpi"=192 }
Write-Host "Launcher icons..."
foreach ($f in $sizes.Keys) {
    Create-LauncherIcon $sizes[$f] "$resDir\$f\ic_launcher.png"
    Copy-Item "$resDir\$f\ic_launcher.png" "$resDir\$f\ic_launcher_round.png"
    Write-Host "  $f done"
}

$srcImg.Dispose()
Write-Host "`nDone!"
