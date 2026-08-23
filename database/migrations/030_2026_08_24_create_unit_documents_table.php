<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('property_id');
            $table->uuid('owner_id')->nullable();
            $table->string('type');
            $table->string('custom_title')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('file_path');
            $table->string('source')->default('uploaded');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['property_id', 'type']);
        });

        DB::table('properties')->orderBy('id')->each(function ($property) {
            $now = now();
            if (filled($property->dtcm_unit_permit ?? null)) {
                DB::table('unit_documents')->insert([
                    'id' => (string) Str::uuid(), 'property_id' => $property->id,
                    'owner_id' => $property->landlord_id, 'type' => 'dtcm_permit',
                    'reference_no' => $property->dtcm_permit_no, 'expires_at' => $property->dtcm_permit_expiry,
                    'file_path' => $property->dtcm_unit_permit, 'source' => 'legacy_property', 'notes' => 'Imported from unit record.',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            if (filled($property->title_deed ?? null)) {
                DB::table('unit_documents')->insert([
                    'id' => (string) Str::uuid(), 'property_id' => $property->id,
                    'owner_id' => $property->landlord_id, 'type' => 'title_deed',
                    'file_path' => $property->title_deed, 'source' => 'legacy_property', 'notes' => 'Imported from unit record.',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_documents');
    }
};
