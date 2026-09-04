<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_inspections', function (Blueprint $t) {
            $t->json('draft_payload')->nullable();
            $t->unsignedInteger('draft_revision')->default(0);
        });
        Schema::create('inspection_upload_tokens', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('inspection_id')->index();
            $t->uuid('item_id');
            $t->text('path');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_upload_tokens');
        Schema::table('booking_inspections', fn (Blueprint $t) => $t->dropColumn(['draft_payload', 'draft_revision']));
    }
};
