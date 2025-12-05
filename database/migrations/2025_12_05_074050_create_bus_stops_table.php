<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bus_stops', function (Blueprint $table) {
            $table->id();

            // ✅ Core data
            $table->string('name');                        // Pieturas nosaukums
            $table->string('stop_code')->index();         // Pieturas kods
            $table->unsignedBigInteger('unified_code')->nullable(); // Vienotais kods (can be NULL)

            // ✅ Coordinates
            $table->decimal('latitude', 10, 7)->index();  
            $table->decimal('longitude', 10, 7)->index(); 

            // ✅ Location details
            $table->string('road_side')->nullable();      
            $table->string('road_number')->nullable();    
            $table->string('street')->nullable();         
            $table->string('municipality')->nullable();  
            $table->string('parish')->nullable();         
            $table->string('village')->nullable();        

            $table->timestamps();

            // ✅ ✅ ✅ CRITICAL UNIQUE KEYS FOR UPSERT ✅ ✅ ✅

            // For rows that HAVE unified_code (left/right handled via road_side)
            $table->unique(['unified_code', 'road_side'], 'bus_stops_unified_road_unique');

            // For rows that DO NOT have unified_code
            $table->unique(['stop_code', 'road_side'], 'bus_stops_stop_road_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_stops');
    }
};
