<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $path = database_path('migrations/ecommerce_schema.sql');
        if (file_exists($path)) {
            $driver = DB::getDriverName();

            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            DB::unprepared(file_get_contents($path));

            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No automatic rollback provided for raw SQL import.
        // You can restore from a dump or write DROP TABLE statements here if desired.
    }
};
