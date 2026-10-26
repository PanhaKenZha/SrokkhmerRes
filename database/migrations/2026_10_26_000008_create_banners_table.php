<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('banners',function(Blueprint $t){
            $t->id();$t->string('title',120);$t->string('image',2000);$t->unsignedSmallInteger('image_width')->nullable();$t->unsignedSmallInteger('image_height')->nullable();
            $t->string('link_url',2000)->nullable();$t->string('button_label',40)->nullable();$t->boolean('is_active')->default(true);$t->unsignedSmallInteger('sort_order')->default(0);$t->timestamps();
        });
        // Starter banners shipped in public/images/banners.
        $now=now();
        DB::table('banners')->insert([
            ['title'=>'Srok Khmer Restaurant · Authentic Khmer cuisine','image'=>'/images/banners/srok-khmer-table.jpg','image_width'=>1916,'image_height'=>821,'link_url'=>'/#menu','button_label'=>'Order now','is_active'=>true,'sort_order'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['title'=>'Fresh ingredients, traditional recipes, local flavors','image'=>'/images/banners/srok-khmer-heritage.jpg','image_width'=>2055,'image_height'=>447,'link_url'=>'/about','button_label'=>'Our story','is_active'=>true,'sort_order'=>2,'created_at'=>$now,'updated_at'=>$now],
            ['title'=>'Good food, good people, better moments','image'=>'/images/banners/srok-khmer-angkor-sunset.jpg','image_width'=>1097,'image_height'=>311,'link_url'=>'/restaurants','button_label'=>'Find a restaurant','is_active'=>true,'sort_order'=>3,'created_at'=>$now,'updated_at'=>$now],
            ['title'=>'Khmer food, quality ingredients, family friendly','image'=>'/images/banners/srok-khmer-family-table.jpg','image_width'=>951,'image_height'=>311,'link_url'=>'/#menu','button_label'=>'See the menu','is_active'=>true,'sort_order'=>4,'created_at'=>$now,'updated_at'=>$now],
        ]);
    }
    public function down(): void {Schema::dropIfExists('banners');}
};
