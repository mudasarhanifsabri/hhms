<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'id_document_back')) {
                $table->string('id_document_back')->nullable()->after('id_document');
            }

            if (! Schema::hasColumn('users', 'nationality')) {
                $table->string('nationality')->nullable()->after('eid_passport_no');
            }

            if (! Schema::hasColumn('users', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'gender')) {
                $table->string('gender')->nullable()->after('nationality');
            }

            if (! Schema::hasColumn('users', 'id_issue_date')) {
                $table->date('id_issue_date')->nullable()->after('gender');
            }

            if (! Schema::hasColumn('users', 'id_expiry_date')) {
                $table->date('id_expiry_date')->nullable()->after('id_issue_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['id_document_back', 'nationality', 'name_ar', 'gender', 'id_issue_date', 'id_expiry_date'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
