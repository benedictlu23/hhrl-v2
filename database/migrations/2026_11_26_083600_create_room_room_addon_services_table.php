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
        if (! Schema::hasTable('room_room_addon_services')) {
            Schema::create('room_room_addon_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
                $table->foreignId('addon_service_id')->constrained('room_addon_services')->cascadeOnDelete();
                $table->unsignedSmallInteger('max_quantity')->nullable()->default(null);
                $table->timestamps();

                $table->unique(['room_id', 'addon_service_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_room_addon_services');
    }
};
