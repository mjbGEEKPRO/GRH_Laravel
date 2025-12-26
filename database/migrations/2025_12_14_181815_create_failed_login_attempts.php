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
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('ip_address', 45)->index();
            $table->text('user_agent')->nullable();
            $table->enum('reason', [
                'wrong_password', 
                'account_blocked', 
                'account_not_found', 
                'too_many_attempts',
                'ip_blocked',
                'account_locked',
                'account_not_approved'
            ])->default('wrong_password');
            $table->timestamp('attempted_at')->useCurrent();
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['email', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_login_attempts');
    }
};