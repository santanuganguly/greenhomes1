<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('configuration')->nullable()->after('project');  // e.g. "2 BHK"
            $table->string('land_location')->nullable()->after('owns_land');
            $table->string('plot_size')->nullable()->after('land_location');
            $table->string('builtup_area')->nullable()->after('area');
            $table->boolean('human_requested')->default(false)->after('status');
            $table->string('site_visit_date')->nullable()->after('human_requested');
            $table->string('site_visit_time')->nullable()->after('site_visit_date');
            $table->text('requirement')->nullable()->after('site_visit_time');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'configuration', 'land_location', 'plot_size', 'builtup_area',
                'human_requested', 'site_visit_date', 'site_visit_time', 'requirement'
            ]);
        });
    }
};
