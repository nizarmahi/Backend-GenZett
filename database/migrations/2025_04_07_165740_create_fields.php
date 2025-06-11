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
        Schema::create('fields', function (Blueprint $table) {
            $table->id('fieldId');
            $table->unsignedBigInteger('locationId');
            $table->foreign('locationId')->references('locationId')->on('locations')->onDelete('cascade');

            $table->unsignedBigInteger('sportId');
            $table->foreign('sportId')->references('sportId')->on('sports')->onDelete('cascade');

            $table->string('name',30);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
