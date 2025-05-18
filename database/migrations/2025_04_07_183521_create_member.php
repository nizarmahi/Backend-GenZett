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
        Schema::create('memberships', function (Blueprint $table) {
            $table->id('membershipId');
            $table->unsignedBigInteger('locationId');
            $table->unsignedBigInteger('sportId');
            $table->string('name', 25);
            $table->text('description')->nullable();
            $table->integer('price');
            $table->smallInteger('weeks');
            $table->timestamps();
            $table->foreign('locationId')->references('locationId')->on('locations')->onDelete('cascade');
            $table->foreign('sportId')->references('sportId')->on('sports')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
