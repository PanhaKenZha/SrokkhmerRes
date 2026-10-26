<?php
namespace Tests\Feature;
use App\Models\Restaurant;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RestaurantManagementTest extends TestCase {
    use RefreshDatabase;
    private function manager(): void {$this->actingAs(User::factory()->create(['email'=>config('restaurant.staff_email')]));}
    private function location(array $overrides=[]): array {return array_merge(['name'=>'River Kitchen','address'=>'Street 12, Phnom Penh','phone'=>'012345678','latitude'=>11.56,'longitude'=>104.93,'is_active'=>1],$overrides);}
    private function dish(Restaurant $restaurant,array $overrides=[]): MenuItem {return MenuItem::create(array_merge(['restaurant_id'=>$restaurant->id,'name'=>'New burger','category'=>'Fast food','price'=>525,'is_available'=>true],$overrides));}
    public function test_manager_can_create_update_restaurants_and_menu_items(): void {
        $this->manager();
        $this->get('/staff/restaurants')->assertOk()->assertSee('Add new restaurant');
        $this->post('/staff/restaurants',$this->location())->assertRedirect('/staff/restaurants');
        $restaurant=Restaurant::first();
        $this->get('/staff/restaurants/'.$restaurant->id.'/edit')->assertOk()->assertSee('River Kitchen');
        $this->patch('/staff/restaurants/'.$restaurant->id,$this->location(['name'=>'Updated Kitchen']))->assertRedirect();
        $this->get('/staff/menu')->assertOk()->assertSee('Add menu item');
        $this->post('/staff/menu',['restaurant_id'=>$restaurant->id,'name'=>'New burger','category'=>'Fast food','price_usd'=>'5.25','is_available'=>1,'image'=>'https://www.themealdb.com/images/example.jpg'])->assertRedirect('/staff/menu');
        $item=MenuItem::first();$this->assertSame(525,$item->price);
        $this->get('/staff/menu/'.$item->id.'/edit')->assertOk()->assertSee('5.25');
        $this->patch('/staff/menu/'.$item->id,['restaurant_id'=>$restaurant->id,'name'=>'New burger','category'=>'Fast food','price_usd'=>'6.10','is_available'=>0])->assertRedirect();
        $this->assertDatabaseHas('menu_items',['id'=>$item->id,'price'=>610,'is_available'=>0]);
    }
    public function test_menu_tags_prep_time_add_another_and_filters(): void {
        $this->manager();
        $restaurant=Restaurant::create($this->location());
        $this->post('/staff/menu',['restaurant_id'=>$restaurant->id,'name'=>'Fish amok','category'=>'Khmer classics','price_usd'=>'6.50','prep_minutes'=>20,'tags'=>['popular','spicy'],'is_available'=>1,'next'=>'another'])
            ->assertRedirect('/staff/menu?restaurant_id='.$restaurant->id.'&category=Khmer+classics#management-editor');
        $item=MenuItem::first();$this->assertSame(['popular','spicy'],$item->tags);$this->assertSame(20,$item->prep_minutes);$this->assertSame(['Popular','Spicy'],$item->tagLabels());
        $this->post('/staff/menu',['restaurant_id'=>$restaurant->id,'name'=>'Bad','category'=>'X','price_usd'=>'1','tags'=>['<script>']])->assertSessionHasErrors('tags.0');
        $this->dish($restaurant,['name'=>'Iced coffee','category'=>'Drinks','is_available'=>false]);
        $this->get('/staff/menu?q=amok')->assertOk()->assertSee('Fish amok')->assertDontSee('Iced coffee');
        $this->get('/staff/menu?status=unavailable')->assertOk()->assertSee('Iced coffee')->assertDontSee('Fish amok');
        $this->get('/staff/restaurants?status=hidden')->assertOk()->assertSee('No restaurants match these filters');
        $this->get('/restaurants/'.$restaurant->id)->assertOk()->assertSee('"tags":["Popular","Spicy"]',false);
    }
    public function test_non_managers_cannot_change_data(): void {
        $this->get('/staff/restaurants')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->get('/staff/restaurants')->assertForbidden();$this->get('/staff/menu')->assertForbidden();
        $this->post('/staff/restaurants',$this->location())->assertForbidden();
        $this->post('/staff/menu',[])->assertForbidden();
    }
    public function test_public_map_and_menus_only_show_published_available_data(): void {
        $restaurant=Restaurant::create($this->location());$this->dish($restaurant);
        $this->dish($restaurant,['name'=>'Unavailable secret dish','is_available'=>false]);
        $hidden=Restaurant::create($this->location(['name'=>'Hidden restaurant','is_active'=>false]));
        $this->get('/restaurants')->assertOk()->assertSee('River Kitchen')->assertDontSee('Hidden restaurant')->assertSee('directory-map');
        $this->get('/restaurants/'.$restaurant->id)->assertOk()->assertSee('New burger')->assertDontSee('Unavailable secret dish')->assertSee('data-location-map',false);
        $this->get('/restaurants/'.$hidden->id)->assertNotFound();
    }
    public function test_orders_are_scoped_to_restaurant_and_use_managed_prices(): void {
        $restaurant=Restaurant::create($this->location());$dish=$this->dish($restaurant);
        $other=Restaurant::create($this->location(['name'=>'Other Kitchen']));$otherDish=$this->dish($other);
        $data=['customer'=>'Sokha','phone'=>'012345678','service'=>'Takeaway','restaurant_id'=>$restaurant->id,'items'=>[['id'=>2000000+$dish->id,'quantity'=>2,'price'=>1]]];
        $this->postJson('/orders',$data)->assertCreated()->assertJsonPath('total',1050);
        $this->assertDatabaseHas('orders',['restaurant_id'=>$restaurant->id,'total'=>1050]);
        $data['items'][0]['id']=2000000+$otherDish->id;$this->postJson('/orders',$data)->assertUnprocessable();
        $data['items'][0]['id']=1;$this->postJson('/orders',$data)->assertUnprocessable();
        $dish->update(['is_available'=>false]);$data['items'][0]['id']=2000000+$dish->id;
        $this->postJson('/orders',$data)->assertUnprocessable();
    }
    public function test_delivery_route_starts_from_selected_restaurant_and_quote_cannot_cross_restaurants(): void {
        config(['delivery.server_key'=>'test-key']);
        $restaurant=Restaurant::create($this->location());$dish=$this->dish($restaurant);
        $other=Restaurant::create($this->location(['latitude'=>11.60]));$otherDish=$this->dish($other);
        Http::fake(['*'=>Http::response(['routes'=>[['distanceMeters'=>1000,'duration'=>'120s']]])]);
        $quote=$this->postJson('/delivery/quote',['latitude'=>11.58,'longitude'=>104.94,'address'=>'House 10','restaurant_id'=>$restaurant->id])->assertOk()->json();
        Http::assertSent(fn($r)=>$r['origin']['location']['latLng']['latitude']===11.56);
        $data=['customer'=>'Sokha','phone'=>'012345678','service'=>'Delivery','quote_id'=>$quote['quote_id'],'restaurant_id'=>$other->id,'items'=>[['id'=>2000000+$otherDish->id,'quantity'=>1]]];
        $this->postJson('/orders',$data)->assertUnprocessable();
        $data['restaurant_id']=$restaurant->id;$data['items'][0]['id']=2000000+$dish->id;
        $this->postJson('/orders',$data)->assertCreated();
    }
    public function test_invalid_coordinates_prices_and_photo_schemes_are_rejected(): void {
        $this->manager();$this->post('/staff/restaurants',$this->location(['latitude'=>100]))->assertSessionHasErrors('latitude');
        $restaurant=Restaurant::create($this->location());
        $this->post('/staff/menu',['restaurant_id'=>$restaurant->id,'name'=>'Burger','category'=>'Food','price_usd'=>'1.234','image'=>'javascript:alert(1)'])->assertSessionHasErrors(['price_usd','image']);
        $this->assertDatabaseCount('menu_items',0);
    }
}
