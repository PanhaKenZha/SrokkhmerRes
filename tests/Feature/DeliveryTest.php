<?php
namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliveryTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void {
        parent::setUp();
        config(['delivery.server_key'=>'test-secret-server','delivery.browser_key'=>'test-browser','delivery.latitude'=>11.5564,'delivery.longitude'=>104.9282,'delivery.base_cents'=>100,'delivery.per_km_cents'=>30,'delivery.max_km'=>15]);
        Http::preventStrayRequests();
    }
    private function quote(): array {return ['latitude'=>11.57,'longitude'=>104.93,'address'=>'House 12, Phnom Penh'];}
    private function order(string $id): array {return ['customer'=>'Sokha','phone'=>'012345678','service'=>'Delivery','quote_id'=>$id,'items'=>[['id'=>1,'quantity'=>2]],'delivery_fee'=>0,'total'=>1,'delivery_address'=>'Forged address'];}
    public function test_google_restaurant_search_and_map_render(): void {
        Http::fake(['places.googleapis.com/*'=>Http::response(['places'=>[['id'=>'abc','displayName'=>['text'=>'Test restaurant'],'location'=>['latitude'=>11.56,'longitude'=>104.93]]]])]);
        $this->get('/restaurants')->assertOk()->assertSee('test-browser')->assertDontSee('test-secret-server');
        $this->getJson('/maps/restaurants?latitude=11.56&longitude=104.93&radius=3000')->assertOk()->assertJsonPath('places.0.id','abc');
        Http::assertSent(fn($r)=>$r->url()==='https://places.googleapis.com/v1/places:searchNearby'&&$r['includedTypes']===['restaurant']&&$r['maxResultCount']===20&&$r->hasHeader('X-Goog-Api-Key','test-secret-server'));
        $this->getJson('/maps/restaurants?latitude=100&longitude=104&radius=3000')->assertUnprocessable();
    }
    public function test_delivery_quote_checkout_and_private_tracking(): void {
        Http::fake(['routes.googleapis.com/*'=>Http::response(['routes'=>[['distanceMeters'=>3200,'duration'=>'600s']]])]);
        $quote=$this->postJson('/delivery/quote',$this->quote())->assertOk()->assertJsonPath('fee',220)->json();
        Http::assertSent(fn($r)=>$r['origin']['location']['latLng']['latitude']===11.5564&&$r['destination']['location']['latLng']['latitude']===11.57&&$r['travelMode']==='DRIVE');
        $result=$this->postJson('/orders',$this->order($quote['quote_id']))->assertCreated()->assertJsonPath('total',1520)->json();
        $this->assertDatabaseHas('orders',['delivery_fee'=>220,'delivery_address'=>'House 12, Phnom Penh','delivery_distance_meters'=>3200]);
        $this->get($result['tracking_url'])->assertOk()->assertSee('New')->assertDontSee('House 12')->assertDontSee('012345678');
        $this->postJson('/orders',$this->order($quote['quote_id']))->assertUnprocessable();
    }
    public function test_expired_and_forged_quotes_are_rejected(): void {
        Http::fake(['*'=>Http::response(['routes'=>[['distanceMeters'=>1000,'duration'=>'120s']]])]);
        $quote=$this->postJson('/delivery/quote',$this->quote())->json();
        $this->postJson('/orders',$this->order('11111111-1111-4111-8111-111111111111'))->assertUnprocessable();
        $this->travel(11)->minutes();
        $this->postJson('/orders',$this->order($quote['quote_id']))->assertUnprocessable();
        $this->assertDatabaseCount('orders',0);
    }
    public function test_outside_area_no_route_and_provider_failure(): void {
        Http::fake(['*'=>Http::sequence()->push(['routes'=>[['distanceMeters'=>16000,'duration'=>'1800s']]])->push(['routes'=>[]])->push([],403)]);
        $this->postJson('/delivery/quote',$this->quote())->assertUnprocessable();
        $this->postJson('/delivery/quote',$this->quote())->assertUnprocessable();
        $this->postJson('/delivery/quote',$this->quote())->assertStatus(503)->assertDontSee('test-secret-server');
    }
    public function test_missing_keys_and_pickup_configuration_fail_clearly(): void {
        config(['delivery.server_key'=>null,'delivery.browser_key'=>null]);
        $this->get('/restaurants')->assertOk()->assertSee('Map setup needed');
        $this->getJson('/maps/restaurants?latitude=11.56&longitude=104.93&radius=3000')->assertStatus(503);
        config(['delivery.latitude'=>null]);
        $this->postJson('/delivery/quote',$this->quote())->assertStatus(503);
        Http::assertNothingSent();
    }
    public function test_staff_can_dispatch_delivery_with_rider_and_complete_it(): void {
        Http::fake(['*'=>Http::response(['routes'=>[['distanceMeters'=>1000,'duration'=>'120s']]])]);
        $quote=$this->postJson('/delivery/quote',$this->quote())->json();
        $this->postJson('/orders',$this->order($quote['quote_id']))->assertCreated();
        $order=Order::first();
        $this->actingAs(User::factory()->create(['email'=>config('restaurant.staff_email')]));
        $this->get('/staff')->assertOk()->assertSee('House 12')->assertSee('Open delivery directions');
        $this->patch('/staff/orders/'.$order->id,['status'=>'Out for delivery'])->assertSessionHasErrors();
        $this->patch('/staff/orders/'.$order->id,['status'=>'Out for delivery','rider_name'=>'Dara','rider_phone'=>'012345679'])->assertRedirect();
        $this->assertDatabaseHas('orders',['status'=>'Out for delivery','rider_name'=>'Dara']);
        $this->patch('/staff/orders/'.$order->id,['status'=>'Delivered'])->assertRedirect();
        $this->assertDatabaseHas('orders',['status'=>'Delivered']);
    }
}
