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
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('title');
            $table->string('department')->nullable()->index();
            $table->text('description');
            $table->string('status')->default('draft')->index();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('closes_at')->nullable()->index();
            $table->string('awarded_to')->nullable();
            $table->decimal('award_amount', 12, 2)->nullable();
            $table->date('awarded_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
