<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('customer');
            $table->string('phone');
            $table->string('service');
            $table->unsignedTinyInteger('table_number')->nullable();
            $table->text('notes')->nullable();
            $table->json('items');
            $table->unsignedInteger('total');
            $table->string('status')->default('New');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('orders'); }
};
