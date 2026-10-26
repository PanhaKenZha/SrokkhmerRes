<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('restaurants',function(Blueprint $t){
            $t->id();$t->string('name');$t->text('description')->nullable();$t->string('phone');$t->text('address');
            $t->decimal('latitude',10,7);$t->decimal('longitude',10,7);$t->boolean('is_active')->default(true);$t->timestamps();
        });
        Schema::create('menu_items',function(Blueprint $t){
            $t->id();$t->foreignId('restaurant_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('kh')->nullable();
            $t->string('category');$t->text('description')->nullable();$t->text('image')->nullable();$t->unsignedInteger('price');
            $t->boolean('is_available')->default(true);$t->timestamps();
        });
        Schema::table('orders',fn(Blueprint $t)=>$t->foreignId('restaurant_id')->nullable()->constrained()->restrictOnDelete());
    }
    public function down(): void {
        Schema::table('orders',fn(Blueprint $t)=>$t->dropConstrainedForeignId('restaurant_id'));
        Schema::dropIfExists('menu_items');Schema::dropIfExists('restaurants');
    }
};
