<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('external_id')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('area')->nullable();
            $table->text('image')->nullable();
            $table->text('instructions');
            $table->json('ingredients');
            $table->text('youtube')->nullable();
            $table->text('source')->nullable();
            $table->unsignedInteger('price');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('meals'); }
};
