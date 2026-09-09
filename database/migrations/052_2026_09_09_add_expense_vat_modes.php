<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('cost_vat_mode', 20)->default('excluded')->after('vat_rate');
            $table->decimal('sale_vat_rate', 5, 2)->default(5)->after('sale_vat_included');
            $table->string('sale_vat_mode', 20)->default('excluded')->after('sale_vat_rate');
        });
        DB::table('expenses')->where('vat_amount', '<=', 0)->update(['cost_vat_mode' => 'none']);
        DB::table('expenses')->where('sale_vat_amount', '<=', 0)->update(['sale_vat_mode' => 'none']);
        DB::table('expenses')->where('sale_vat_amount', '>', 0)->where('sale_vat_included', true)->update(['sale_vat_mode' => 'included']);
        DB::table('expenses')->where('sale_vat_amount', '>', 0)->update(['sale_vat_rate' => DB::raw('vat_rate')]);
    }

    public function down(): void
    {
        Schema::table('expenses', fn (Blueprint $table) => $table->dropColumn(['cost_vat_mode', 'sale_vat_rate', 'sale_vat_mode']));
    }
};
