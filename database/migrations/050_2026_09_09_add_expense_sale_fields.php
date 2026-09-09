<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('sale_net_amount', 12, 2)->default(0)->after('gross_amount');
            $table->decimal('sale_vat_amount', 12, 2)->default(0)->after('sale_net_amount');
            $table->decimal('sale_gross_amount', 12, 2)->default(0)->after('sale_vat_amount');
            $table->boolean('sale_vat_included')->default(false)->after('sale_gross_amount');
            $table->decimal('profit_amount', 12, 2)->default(0)->after('sale_vat_included');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', fn (Blueprint $table) => $table->dropColumn(['sale_net_amount', 'sale_vat_amount', 'sale_gross_amount', 'sale_vat_included', 'profit_amount']));
    }
};
