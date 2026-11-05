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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 160)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('company', 160)->nullable();
            $table->string('role', 120)->nullable();
            $table->string('source', 30);
            $table->string('status', 30)->default('new');
            $table->string('ip_hash', 64)->nullable();
            $table->json('utm')->nullable();
            $table->timestamp('consent_at');
            $table->timestamp('last_submitted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
