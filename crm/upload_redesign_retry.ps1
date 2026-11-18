$user = "u495244335.u495244335crm:GreenHomesCRM@2026!"
$server = "ftp://ftp.thegreen.homes/"

$files = @(
    "resources/views/dashboard.blade.php",
    "resources/views/livewire/marketing/campaigns-list.blade.php"
)

foreach ($file in $files) {
    Write-Host "Uploading $file..."
    $localPath = "c:\Users\91963\OneDrive\Documents\claudeee\green-homes\crm\$file"
    $remotePath = "$server$file"
    
    $cmd = "curl.exe -sS --ftp-create-dirs -T `"$localPath`" `"$remotePath`" --user `"$user`""
    Invoke-Expression $cmd
}

Write-Host "Done!"
