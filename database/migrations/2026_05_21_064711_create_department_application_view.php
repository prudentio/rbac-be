<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_department_application_access AS
            SELECT
                d.id AS department_id,
                d.name AS department_name,

                a.id AS application_id,
                a.name AS application_name

            FROM application_department_access ada

            INNER JOIN departments d
                ON d.id = ada.department_id

            INNER JOIN applications a
                ON a.id = ada.application_id
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_department_application_access');
    }
};