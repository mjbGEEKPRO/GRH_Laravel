<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
       public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('projet_id')->nullable();
            $table->unsignedBigInteger('assigne_a_user_id');
            $table->date('date_echeance');
            $table->enum('priorite', ['Basse', 'Normale', 'Haute'])->default('Normale');
            $table->enum('statut', ['En cours', 'Terminé', 'En attente'])->default('En attente');
            $table->unsignedBigInteger('created_by');
            $table->foreign('projet_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('assigne_a_user_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
