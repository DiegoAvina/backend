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
        Schema::create('solutions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('headline', 255)->nullable();
            $table->string('summary', 500);
            $table->string('icon', 50)->nullable();
            $table->json('signals')->nullable();
            $table->json('approach')->nullable();
            $table->json('outcomes')->nullable();
            $table->json('seo')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('service_solution', function (Blueprint $table) {
            $table->foreignId('solution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['solution_id', 'service_id']);
            $table->index('service_id');
        });

        Schema::create('product_solution', function (Blueprint $table) {
            $table->foreignId('solution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['solution_id', 'product_id']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_solution');
        Schema::dropIfExists('service_solution');
        Schema::dropIfExists('solutions');
    }
};
