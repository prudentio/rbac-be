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
            CREATE OR REPLACE VIEW v_user_application_access AS

            SELECT
                x.user_id,
                x.application_id,
                x.source_type
            FROM (

                SELECT
                    u.id AS user_id,
                    ada.application_id,
                    'Department' AS source_type
                FROM users u
                JOIN application_department_access ada
                    ON ada.department_id = u.department_id

                UNION ALL

                SELECT
                    u.id AS user_id,
                    ara.application_id,
                    'Role' AS source_type
                FROM users u
                JOIN application_role_access ara
                    ON ara.role_id = u.role_id

                UNION ALL

                SELECT
                    aua.user_id,
                    aua.application_id,
                    'Special' AS source_type
                FROM application_user_access aua
                WHERE aua.is_denied = false

            ) x

            WHERE NOT EXISTS (
                SELECT 1
                FROM application_user_access deny
                WHERE deny.user_id = x.user_id
                AND deny.application_id = x.application_id
                AND deny.is_denied = true
            );
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_user_application_access");
    }
};
