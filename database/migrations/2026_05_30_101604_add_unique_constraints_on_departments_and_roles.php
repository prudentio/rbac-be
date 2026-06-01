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
        Schema::table('departments', function (Blueprint $table){
            $table->unique(
                'name',
                'departments_name_unique'
            );        
        });

        Schema::table('roles', function (Blueprint $table){
            $table->unique(
                'name',
                'roles_name_unique'
            );        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_name_unique');        
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_unique');        
        });

    }
};
