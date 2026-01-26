<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add new subject_to_availability field if it doesn't exist
            if (!Schema::hasColumn('products', 'subject_to_availability')) {
                $table->boolean('subject_to_availability')->default(false)->comment('If true, shows product but prevents purchase')->after('is_featured');
            }

            // Remove stock-related fields if they exist
            if (Schema::hasColumn('products', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }
            if (Schema::hasColumn('products', 'low_stock_threshold')) {
                $table->dropColumn('low_stock_threshold');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restore stock fields
            $table->integer('stock_quantity')->default(0)->after('cost_price');
            $table->integer('low_stock_threshold')->default(10)->after('stock_quantity');

            // Remove subject_to_availability field
            $table->dropColumn('subject_to_availability');
        });
    }
};
