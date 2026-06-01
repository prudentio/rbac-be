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
        Schema::table('application_categories', function (Blueprint $table){
            $table->unique(
                'name',
                'application_categories_name_unique'
            );        
        });

        Schema::table('application_department_access', function (Blueprint $table) {
            $table->unique(
                ['application_id', 'department_id'],
                'app_dept_access_unique'
            );
        });

        Schema::table('application_role_access', function (Blueprint $table) {
            $table->unique(
                ['application_id', 'role_id'],
                'app_role_access_unique'
            );
        });

        Schema::table('application_user_access', function (Blueprint $table) {
            $table->unique(
                ['application_id', 'user_id'],
                'app_user_access_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_user_access', function (Blueprint $table) {
            $table->dropUnique('app_user_access_unique');
        });

        Schema::table('application_role_access', function (Blueprint $table) {
            $table->dropUnique('app_role_access_unique');
        });

         Schema::table('application_department_access', function (Blueprint $table) {
            $table->dropUnique('app_dept_access_unique');
        });

        Schema::table('application_categories', function (Blueprint $table) {
            $table->dropUnique('application_categories_name_unique');        
        });

    }
};
