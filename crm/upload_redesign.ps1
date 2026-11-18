$user = "u495244335.u495244335crm:GreenHomesCRM@2026!"
$server = "ftp://ftp.thegreen.homes/"

$files = @(
    "resources/views/layouts/app.blade.php",
    "resources/views/layouts/guest.blade.php",
    "resources/views/livewire/layout/navigation.blade.php",
    "resources/views/livewire/management-dashboard.blade.php",
    "resources/views/livewire/leads-list.blade.php",
    "resources/views/livewire/inbox.blade.php",
    "resources/views/livewire/marketing/campaigns.blade.php",
    "resources/views/livewire/marketing/campaign-create.blade.php",
    "resources/views/livewire/marketing/campaign-details.blade.php",
    "resources/views/livewire/settings-manager.blade.php",
    "resources/views/livewire/projects-manager.blade.php",
    "resources/views/livewire/flow-builder.blade.php",
    "resources/views/livewire/bot-message-editor.blade.php",
    "resources/views/components/dropdown.blade.php",
    "resources/views/components/modal.blade.php",
    "public/build/manifest.json",
    "public/build/assets/app-Cp9YB3qU.css",
    "public/build/assets/app-BvRk9kiK.js"
)

foreach ($file in $files) {
    Write-Host "Uploading $file..."
    $localPath = "c:\Users\91963\OneDrive\Documents\claudeee\green-homes\crm\$file"
    $remotePath = "$server$file"
    
    $cmd = "curl.exe -sS --ftp-create-dirs -T `"$localPath`" `"$remotePath`" --user `"$user`""
    Invoke-Expression $cmd
}

Write-Host "Done!"
