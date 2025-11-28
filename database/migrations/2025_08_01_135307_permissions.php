<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique()->comment('Nom technique de la permission (ex: users.create)');
            $table->string('slug')->unique()->comment('Slug URL-friendly (ex: users-create)');
            $table->text('description')->nullable()->comment('Description de la permission');
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('nom');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};