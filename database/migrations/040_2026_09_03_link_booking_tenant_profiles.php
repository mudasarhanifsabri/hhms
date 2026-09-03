<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->foreignUuid('tenant_id')->nullable()->constrained('users')->nullOnDelete());
        Schema::table('users', fn (Blueprint $table) => $table->boolean('tenant_profile_required')->default(false));
        \App\Models\Booking::orderBy('created_at')->orderBy('id')->chunk(100, function ($bookings) {
            foreach ($bookings as $booking) {
                \App\Support\BookingTenantProfile::sync($booking);
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropConstrainedForeignId('tenant_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('tenant_profile_required'));
        // Created tenant accounts are preserved, never deleted by rollback.
    }
};
