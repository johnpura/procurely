<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bid_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained()->restrictOnDelete();
            $table->string('method')->index();
            $table->string('receipt_code', 16)->unique();
            $table->string('vendor_name');
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->text('cover_note')->nullable();
            $table->text('internal_notes')->nullable();
            $table->dateTime('submitted_at');
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['bid_id', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_responses');
    }
};
