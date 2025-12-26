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
        Schema::table('roles', function (Blueprint $table) {
            // Ajouter la colonne departement_id si elle n'existe pas
            if (!Schema::hasColumn('roles', 'departement_id')) {
                $table->foreignId('departement_id')
                    ->nullable()
                    ->after('nom')
                    ->constrained('departements')
                    ->onDelete('cascade');
            }
            
            // Ajouter index
            $table->index('departement_id');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['departement_id']);
            $table->dropColumn('departement_id');
        });
    }
};
