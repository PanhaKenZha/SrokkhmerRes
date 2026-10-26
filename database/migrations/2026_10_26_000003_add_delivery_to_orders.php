<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('orders',function(Blueprint $table){
            $table->text('delivery_address')->nullable();
            $table->decimal('delivery_latitude',10,7)->nullable();
            $table->decimal('delivery_longitude',10,7)->nullable();
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->unsignedInteger('delivery_distance_meters')->nullable();
            $table->unsignedInteger('delivery_duration_seconds')->nullable();
            $table->string('rider_name')->nullable();
            $table->string('rider_phone')->nullable();
        });
    }
    public function down(): void {
        Schema::table('orders',fn(Blueprint $table)=>$table->dropColumn(['delivery_address','delivery_latitude','delivery_longitude','delivery_fee','delivery_distance_meters','delivery_duration_seconds','rider_name','rider_phone']));
    }
};
