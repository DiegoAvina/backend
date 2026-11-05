<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Descripción detallada e imagen para el contenido administrable.
     * Todas las columnas son nulas: el contenido existente no cambia.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->longText('body')->nullable()->after('description');
            $table->string('image', 255)->nullable()->after('icon');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->longText('body')->nullable()->after('description');
            $table->string('image', 255)->nullable()->after('icon');
        });

        Schema::table('solutions', function (Blueprint $table) {
            $table->longText('body')->nullable()->after('summary');
            $table->string('image', 255)->nullable()->after('icon');
        });

        Schema::table('industries', function (Blueprint $table) {
            $table->string('image', 255)->nullable()->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industries', fn (Blueprint $table) => $table->dropColumn('image'));
        Schema::table('solutions', fn (Blueprint $table) => $table->dropColumn(['body', 'image']));
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn(['body', 'image']));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['body', 'image']));
    }
};
