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
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('departement_id')->constrained()->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('roless', function (Blueprint $table) {
            $table->dropForeign('roles_departement_id_foreign');
            $table->dropColumn('departement_id');
        });
    }
};
