<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('road_segments', function (Blueprint $table) {
            $table->id();
            $table->decimal('lat1', 10, 7);
            $table->decimal('lon1', 10, 7);
            $table->decimal('lat2', 10, 7);
            $table->decimal('lon2', 10, 7);
            $table->integer('maxspeed')->default(50); // km/h
            $table->timestamps();

            $table->index(['lat1','lon1','lat2','lon2']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('road_segments');
    }
};
