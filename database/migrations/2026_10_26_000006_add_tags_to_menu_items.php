<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('menu_items',function(Blueprint $t){$t->json('tags')->nullable();$t->unsignedSmallInteger('prep_minutes')->nullable();});
    }
    public function down(): void {
        Schema::table('menu_items',function(Blueprint $t){$t->dropColumn(['tags','prep_minutes']);});
    }
};
