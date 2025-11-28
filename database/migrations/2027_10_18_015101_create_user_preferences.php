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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Notifications
            $table->boolean('notif_email')->default(true);
            $table->boolean('notif_task_reminders')->default(true);
            $table->boolean('notif_project_updates')->default(true);
            $table->boolean('notif_deadline_alerts')->default(true);
            
            // Préférences générales
            $table->string('language')->default('fr');
            $table->integer('auto_logout')->default(60); 
            
            
            $table->timestamps();
            
            // Index
            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_preferences');
    }
};
