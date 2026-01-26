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
        if (Schema::hasColumn('shipping_rates', 'base_price')) {
            Schema::table('shipping_rates', function (Blueprint $table) {
                $table->dropColumn('base_price');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->decimal('base_price', 10, 2)->default(0.00)->comment('Base/flat rate')->after('price_per_kg_volumetric');
        });
    }
};
