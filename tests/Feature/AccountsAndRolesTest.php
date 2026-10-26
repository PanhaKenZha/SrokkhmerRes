<?php
namespace Tests\Feature;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsAndRolesTest extends TestCase {
    use RefreshDatabase;
    private function order(): array {return ['customer'=>'Sokha','phone'=>'012 345 678','service'=>'Takeaway','items'=>[['id'=>1,'quantity'=>1]]];}
    private function user(string $role): User {return User::factory()->create(['role'=>$role]);}

    public function test_customers_register_sign_in_and_order(): void {
        config(['restaurant.order_requires_login'=>true]);
        $this->postJson('/orders',$this->order())->assertUnauthorized()->assertJsonPath('login_url','/login?return=%2F%23menu');
        $this->get('/register')->assertOk()->assertSee('Create an account');
        $this->post('/register',['name'=>'Dara Customer','email'=>'dara@example.com','phone'=>'012 999 888','password'=>'good-password','password_confirmation'=>'good-password','role'=>'admin'])->assertRedirect('/');
        $dara=User::where('email','dara@example.com')->first();
        $this->assertSame('customer',$dara->role,'Registration must never take the role from the form.');
        $this->assertAuthenticatedAs($dara);
        $this->get('/')->assertSee('Ordering as',false)->assertSee('value="012 999 888"',false);
        $this->postJson('/orders',$this->order())->assertCreated();
        $this->assertSame($dara->id,Order::first()->user_id);
        $this->get('/account/orders')->assertOk()->assertSee('Fish amok')->assertSee('Track order');
        $this->get('/staff')->assertForbidden();
        $this->post('/logout');
        $this->post('/login',['email'=>'dara@example.com','password'=>'good-password'])->assertRedirect('/');
    }

    public function test_login_returns_to_safe_paths_only(): void {
        User::factory()->create(['email'=>'mey@example.com','password'=>bcrypt('good-password')]);
        $this->get('/login?return=%2Frestaurants%2F1%23menu');
        $this->post('/login',['email'=>'mey@example.com','password'=>'good-password'])->assertRedirect('/restaurants/1#menu');
        $this->post('/logout');
        $this->get('/login?return=%2F%2Fevil.example');
        $this->post('/login',['email'=>'mey@example.com','password'=>'good-password'])->assertRedirect('/');
    }

    public function test_each_role_sees_only_what_it_may_use(): void {
        $staff=$this->user('staff');
        $this->actingAs($staff)->get('/staff')->assertOk()->assertDontSee('Completed order sales')->assertDontSee('/staff/menu"',false)->assertDontSee('/staff/team"',false);
        foreach(['/staff/menu','/staff/restaurants','/staff/team'] as $page) $this->get($page)->assertForbidden();
        $owner=$this->user('owner');
        $this->actingAs($owner)->get('/staff')->assertOk()->assertSee('Completed order sales');
        foreach(['/staff/menu','/staff/restaurants','/staff/team'] as $page) $this->get($page)->assertOk();
        $this->actingAs($this->user('customer'))->get('/staff')->assertForbidden();
    }

    public function test_owners_manage_staff_but_only_admins_elevate(): void {
        $owner=$this->user('owner');$admin=$this->user('admin');$customer=$this->user('customer');
        $this->actingAs($owner)->post('/staff/team',['name'=>'Vanna','email'=>'vanna@example.com','password'=>'good-password','role'=>'staff'])->assertRedirect('/staff/team');
        $this->assertSame('staff',User::where('email','vanna@example.com')->value('role'));
        $this->post('/staff/team',['name'=>'Boss','email'=>'boss@example.com','password'=>'good-password','role'=>'admin'])->assertSessionHasErrors('role');
        $this->patch('/staff/team/'.$customer->id,['role'=>'staff'])->assertRedirect();
        $this->assertSame('staff',$customer->fresh()->role);
        $this->patch('/staff/team/'.$customer->id,['role'=>'owner'])->assertSessionHasErrors('role');
        $this->patch('/staff/team/'.$admin->id,['role'=>'customer'])->assertForbidden();
        $this->patch('/staff/team/'.$owner->id,['role'=>'staff'])->assertForbidden();
        $this->actingAs($admin)->patch('/staff/team/'.$customer->id,['role'=>'owner'])->assertRedirect();
        $this->assertSame('owner',$customer->fresh()->role);
        $this->get('/staff/team')->assertOk()->assertSee('What each role can do');
    }

    public function test_last_admin_cannot_be_removed_and_staff_email_is_admin(): void {
        $configured=User::factory()->create(['email'=>config('restaurant.staff_email')]);
        $this->assertSame('admin',$configured->role);
        $other=$this->user('admin');
        $this->actingAs($other)->patch('/staff/team/'.$configured->id,['role'=>'staff'])->assertRedirect();
        $this->assertSame('staff',$configured->fresh()->role);
        $this->actingAs($configured->refresh())->patch('/staff/team/'.$other->id,['role'=>'staff'])->assertForbidden();
        $configured->forceFill(['role'=>'admin'])->save();
        $this->actingAs($configured)->patch('/staff/team/'.$other->id,['role'=>'staff']);
        $this->assertSame('staff',$other->fresh()->role);
        $this->assertSame(1,User::where('role','admin')->count());
    }
}
