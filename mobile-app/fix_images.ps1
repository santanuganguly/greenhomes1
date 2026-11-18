Add-Type -AssemblyName System.Drawing
$src = "C:\Users\91963\Downloads\Green_Homes_logo_3D_presentation_202607231339.jpeg"
if (Test-Path $src) {
    $img = [System.Drawing.Image]::FromFile($src)
    
    Remove-Item "android\app\src\main\res\drawable\splash_logo.png" -ErrorAction SilentlyContinue
    $img.Save("android\app\src\main\res\drawable\splash.png", [System.Drawing.Imaging.ImageFormat]::Png)
    
    $dirs = @('mipmap-hdpi','mipmap-mdpi','mipmap-xhdpi','mipmap-xxhdpi','mipmap-xxxhdpi')
    foreach ($d in $dirs) {
        $img.Save("android\app\src\main\res\$d\ic_launcher.png", [System.Drawing.Imaging.ImageFormat]::Png)
        $img.Save("android\app\src\main\res\$d\ic_launcher_round.png", [System.Drawing.Imaging.ImageFormat]::Png)
        $img.Save("android\app\src\main\res\$d\ic_launcher_foreground.png", [System.Drawing.Imaging.ImageFormat]::Png)
    }
    $img.Dispose()
    Write-Host "Images successfully converted and replaced!"
} else {
    Write-Host "Source image not found."
}
