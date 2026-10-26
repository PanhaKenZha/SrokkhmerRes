<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users',function(Blueprint $t){$t->string('role',20)->default('customer')->index();$t->string('phone',20)->nullable();});
        Schema::table('orders',fn(Blueprint $t)=>$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete());
        // The existing STAFF_EMAIL account keeps full access as the first admin.
        DB::table('users')->where('email',config('restaurant.staff_email'))->update(['role'=>'admin']);
    }
    public function down(): void {
        Schema::table('orders',fn(Blueprint $t)=>$t->dropConstrainedForeignId('user_id'));
        Schema::table('users',function(Blueprint $t){$t->dropIndex(['role']);$t->dropColumn(['role','phone']);});
    }
};
