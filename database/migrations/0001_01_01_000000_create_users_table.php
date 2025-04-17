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
        Schema::create('users', function (Blueprint $table) {
            $table->id('userId');
            $table->enum('role', ['guest', 'user', 'member', 'admin'])->default('guest'); // dari ERD
            $table->string('username', 16)->unique();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('phone', 15)->nullable()->unique();
            $table->string('password', 255); // panjang untuk hash bcrypt
            $table->timestamp('created_at')->useCurrent();
            $table->rememberToken()->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('userId')->nullable()->index();
            $table->string('ipAddress', 45)->nullable();
            $table->text('userAgent')->nullable();
            $table->longText('payload');
            $table->integer('lastActivity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
