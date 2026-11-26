<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('rooms') && ! Schema::hasColumn('rooms', 'type')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->string('type', 20)->default('room')->after('booking_type')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'type')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
