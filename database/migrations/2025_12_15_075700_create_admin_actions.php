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
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->enum('action_type', [
                'block_user',
                'unblock_user',
                'unblock_ip',
                'modify_permissions',
                'delete_user',
                'reset_password'
            ]);
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->string('target_ip', 45)->nullable();
            $table->text('details')->nullable();
            $table->timestamp('created_at');
            


            // Index pour optimiser les requêtes
            $table->index(['admin_id', 'created_at']);
            $table->index(['action_type', 'created_at']);
            $table->index('target_user_id');

            
            
            // Clé étrangère
            $table->foreign('admin_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};