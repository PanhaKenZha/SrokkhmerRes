<?php
namespace Tests\Feature;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class RestaurantTest extends TestCase {
    use RefreshDatabase;
    private function payload(): array { return ['customer'=>'Sokha','phone'=>'012 345 678','service'=>'Dine in','table_number'=>4,'items'=>[['id'=>1,'quantity'=>2,'price'=>1]],'total'=>1]; }
    public function test_menu_and_login_render(): void {
        $this->get('/')->assertOk()->assertSee('SROK KHMER');
        $this->get('/login')->assertOk();
    }
    public function test_order_uses_catalog_prices(): void {
        $this->postJson('/orders',$this->payload())->assertCreated()->assertJsonPath('total',1300);
        $this->assertDatabaseHas('orders',['total'=>1300,'customer'=>'Sokha']);
    }
    public function test_invalid_dishes_quantities_and_missing_table_are_rejected(): void {
        $data=$this->payload();$data['items'][0]['id']=99;
        $this->postJson('/orders',$data)->assertUnprocessable();
        $data=$this->payload();$data['items'][0]['quantity']=21;
        $this->postJson('/orders',$data)->assertUnprocessable();
        $data=$this->payload();unset($data['table_number']);
        $this->postJson('/orders',$data)->assertUnprocessable();
        $this->assertDatabaseCount('orders',0);
    }
    public function test_guests_and_nonstaff_cannot_manage_orders(): void {
        $this->get('/staff')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/staff')->assertForbidden();
    }
    public function test_manager_can_update_orders_and_notes_are_escaped(): void {
        $data=$this->payload();$data['notes']='<script>alert(1)</script>';
        $this->postJson('/orders',$data)->assertCreated();
        $manager=User::factory()->create(['email'=>config('restaurant.staff_email')]);
        $this->actingAs($manager)->get('/staff')->assertOk()->assertSee('&lt;script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
        $this->patch('/staff/orders/'.Order::first()->id,['status'=>'Preparing'])->assertRedirect();
        $this->assertDatabaseHas('orders',['status'=>'Preparing']);
    }
    public function test_manager_login_and_logout(): void {
        User::factory()->create(['email'=>config('restaurant.staff_email'),'password'=>bcrypt('a-strong-password')]);
        $this->post('/login',['email'=>config('restaurant.staff_email'),'password'=>'wrong'])->assertSessionHasErrors();
        $this->post('/login',['email'=>config('restaurant.staff_email'),'password'=>'a-strong-password'])->assertRedirect('/staff');
        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
