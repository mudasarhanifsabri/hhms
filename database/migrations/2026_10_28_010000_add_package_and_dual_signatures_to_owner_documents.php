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
        Schema::table('property_owner_documents', function (Blueprint $table) {
            $table->dropUnique(['signing_token']);
            $table->index('signing_token');
            $table->uuid('package_id')->nullable()->index()->after('landlord_id');
            $table->string('agreement_type')->default('new')->after('package_id');
            $table->string('owner_signed_name')->nullable()->after('signature_data');
            $table->string('company_token')->nullable()->index()->after('signing_token');
            $table->longText('company_signature_data')->nullable()->after('owner_signed_name');
            $table->string('company_signed_name')->nullable()->after('company_signature_data');
            $table->string('company_signer_email')->nullable()->after('company_signed_name');
            $table->timestamp('company_sent_at')->nullable()->after('signed_at');
            $table->timestamp('company_viewed_at')->nullable()->after('company_sent_at');
            $table->timestamp('company_signed_at')->nullable()->after('company_viewed_at');
            $table->timestamp('completed_at')->nullable()->after('company_signed_at');
        });

        DB::table('property_owner_documents')->orderBy('id')->chunk(100, function ($documents) {
            foreach ($documents as $document) {
                DB::table('property_owner_documents')->where('id', $document->id)->update([
                    'package_id' => (string) Str::uuid(),
                    'agreement_type' => 'new',
                    'owner_signed_name' => null,
                    'company_signed_at' => $document->signed_at,
                    'completed_at' => $document->signed_at,
                    'status' => $document->signed_at ? 'fully_signed' : $document->status,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_owner_documents', function (Blueprint $table) {
            $table->dropIndex(['signing_token']);
            $table->dropIndex(['package_id']);
            $table->dropIndex(['company_token']);
            $table->dropColumn(['package_id','agreement_type','owner_signed_name','company_token','company_signature_data','company_signed_name','company_signer_email','company_sent_at','company_viewed_at','company_signed_at','completed_at']);
        });
    }
};
