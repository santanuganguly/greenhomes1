<?php
use App\Models\Setting;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    Setting::updateOrCreate(['key' => 'meta_access_token'], ['value' => 'EAAThE9Sfl9YBSOmWwl0AWZCwNLVF6kxnhK0uJcMgvCbXuVJEvS0iyZCjpGBbCNponewQZA7KLJHDzLXtV7rvn7uEzP2Vlj5Hy2AmTXpuV2lK6ZA4gdhq2L4c4AFDKy2opIUJ63PwvKdJtjPSDZCZCZA4kSbGnOERtfwzbx2WQLUXJzNEzI4SDvsBI3DrrZBQC2jO15xAZB9xkcYbsCfITj225ofEvUtrs3tGSB03A6HFMExuJfYFbiSHZCqKVoDTyI6gDDJUQ0u980mZCfYxmpwcUFg']);
    Setting::updateOrCreate(['key' => 'meta_phone_number_id'], ['value' => '1194759783721722']);
    Setting::updateOrCreate(['key' => 'meta_waba_id'], ['value' => '930850970042236']);
    echo "<h1>Success</h1>";
} catch (\Throwable $e) {
    echo "<h1>Error</h1>";
    echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
}
