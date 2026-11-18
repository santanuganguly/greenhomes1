$user = "u495244335.u495244335crm:GreenHomesCRM@2026!"
$server = "ftp://ftp.thegreen.homes/"

$files = @(
    "database/migrations/2026_08_10_133123_create_bulk_campaigns_table.php",
    "database/migrations/2026_08_10_133124_create_bulk_messages_table.php",
    "app/Models/BulkCampaign.php",
    "app/Models/BulkMessage.php",
    "app/Jobs/SendBulkMessageJob.php",
    "app/Http/Controllers/WebhookController.php",
    "app/Livewire/BulkMessagingManager.php",
    "app/Livewire/BulkCampaignDetail.php",
    "resources/views/livewire/bulk-messaging-manager.blade.php",
    "resources/views/livewire/bulk-campaign-detail.blade.php",
    "resources/views/livewire/layout/navigation.blade.php",
    "routes/web.php"
)

foreach ($file in $files) {
    Write-Host "Uploading $file..."
    $localPath = "c:\Users\91963\OneDrive\Documents\claudeee\green-homes\crm_clean\$file"
    $remotePath = "$server$file"
    
    $cmd = "curl.exe -sS --ftp-create-dirs -T `"$localPath`" `"$remotePath`" --user `"$user`""
    Invoke-Expression $cmd
}

Write-Host "Done!"
