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
        $table->id();
        $table->string('nom');
        $table->string('prenom');
        $table->string('email');
        $table->date('date_naissance');
        $table->string('lieu_naissance');
        $table->string('email_pro')->nullable();
        $table->string('telephone');
        $table->string('situation_famille')->nullable();
        $table->boolean('statut')->default(false);
        $table->boolean('compte')->default(false);
        $table->string('password')->nullable();
        $table->foreignId('role_id')
        ->nullable()
        ->constrained('roles')
        ->onDelete('cascade');
        $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
