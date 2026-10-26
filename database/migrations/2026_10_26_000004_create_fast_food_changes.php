<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('fast_food_changes',function(Blueprint $table){
            $table->id();
            $table->uuid('external_id')->unique();
            $table->string('restaurant_slug');
            $table->string('restaurant_name');
            $table->string('meal_name');
            $table->string('meal_slug');
            $table->string('change_type');
            $table->string('field')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('note')->nullable();
            $table->text('source_url')->nullable();
            $table->dateTime('observed_at');
            $table->timestamps();
            $table->index(['restaurant_slug','observed_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('fast_food_changes'); }
};
