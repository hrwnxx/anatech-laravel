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
        Schema::create('gadgets', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: a category that still has gadgets can't be deleted.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('brand', 50);
            $table->string('gadget_name', 100);
            $table->string('model', 100)->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->text('description')->nullable();
            // Path inside the "public" storage disk, e.g. "gadgets/abc123.jpg".
            $table->string('image')->nullable();
            // Archiving hides a gadget from the catalog without deleting it,
            // so purchase and review history always points at a real product.
            $table->boolean('archived')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gadgets');
    }
};
