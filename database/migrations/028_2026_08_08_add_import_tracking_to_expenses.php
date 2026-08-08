<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'import_source_type')) {
                $table->string('import_source_type')->nullable()->after('invoice_path')->index();
            }

            if (! Schema::hasColumn('expenses', 'import_source_file')) {
                $table->string('import_source_file')->nullable()->after('import_source_type');
            }

            if (! Schema::hasColumn('expenses', 'imported_transaction_id')) {
                $table->string('imported_transaction_id')->nullable()->after('import_source_file')->index();
            }

            if (! Schema::hasColumn('expenses', 'imported_payload')) {
                $table->json('imported_payload')->nullable()->after('imported_transaction_id');
            }

            if (! Schema::hasColumn('expenses', 'needs_review')) {
                $table->boolean('needs_review')->default(false)->after('imported_payload')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            foreach (['import_source_type', 'import_source_file', 'imported_transaction_id', 'imported_payload', 'needs_review'] as $column) {
                if (Schema::hasColumn('expenses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
