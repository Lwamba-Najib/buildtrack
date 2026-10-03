<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->string('consignment_number', 32)->unique();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();

            // PENDING_APPROVAL -> created, stock not yet moved
            // APPROVED         -> stock decremented, out with agent, nothing reconciled yet
            // PARTIALLY_RECONCILED -> at least one reconciliation done, some qty still outstanding
            // RECONCILED        -> everything issued is accounted for (sold + returned + lost)
            // REJECTED          -> never approved, stock never moved
            $table->enum('status', [
                'PENDING_APPROVAL',
                'APPROVED',
                'PARTIALLY_RECONCILED',
                'RECONCILED',
                'REJECTED',
            ])->default('PENDING_APPROVAL');

            $table->date('requested_date');
            $table->date('approved_date')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('rejection_reason')->nullable();

            $table->string('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignments');
    }
};
