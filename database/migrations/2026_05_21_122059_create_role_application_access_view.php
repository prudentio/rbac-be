<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE VIEW v_role_application_access_view AS
            SELECT
                ara.id,
                r.id AS role_id,
                r.name AS role_name,
                a.id AS application_id,
                a.name AS application_name
            FROM application_role_access ara
            JOIN roles r
                ON r.id = ara.role_id
            JOIN applications a
                ON a.id = ara.application_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            DROP VIEW IF EXISTS v_role_application_access_view
        ");
    }
};