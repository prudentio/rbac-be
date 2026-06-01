<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->uuid('department_id')->nullable();
            $table->uuid('role_id')->nullable();
            $table->timestamps();
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->nullOnDelete();
        });

        Schema::create('application_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('url');
            $table->string('icon')->nullable();
            $table->uuid('category_id');
            $table->timestamps();
            $table->foreign('category_id')
                ->references('id')
                ->on('application_categories')
                ->cascadeOnDelete();
        });

        Schema::create('application_department_access', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id');
            $table->uuid('department_id');
            $table->foreign('application_id')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->cascadeOnDelete();
        });

        Schema::create('application_role_access', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id');
            $table->uuid('role_id');
            $table->foreign('application_id')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
        });

        Schema::create('application_user_access', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('application_id');
            $table->boolean('is_denied')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('application_id')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_user_access');
        Schema::dropIfExists('application_role_access');
        Schema::dropIfExists('application_department_access');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('application_categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('departments');
    }
};