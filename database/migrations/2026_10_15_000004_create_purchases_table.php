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
        // A purchase is recorded by an admin for an in-store sale. It is
        // also the proof a customer needs before they can review a gadget.
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gadget_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            // The gadget's price at the moment of sale, so editing a price
            // later never changes past revenue.
            $table->decimal('unit_price', 10, 2);
            $table->date('purchase_date');
            $table->boolean('archived')->default(false)->index();
            $table->timestamps();

            $table->index(['user_id', 'gadget_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
