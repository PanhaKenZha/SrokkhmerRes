<?php
namespace Tests\Feature;

use App\Models\FastFoodChange;
use App\Services\FastFoodFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class FastFoodTest extends TestCase {
    use RefreshDatabase;
    private function record(array $overrides=[]): array {
        return array_merge(['id'=>(string)Str::uuid(),'restaurant_slug'=>'mcdonalds','restaurant_name'=>"McDonald's",'meal_name'=>'Test burger','meal_slug'=>'test-burger','change_type'=>'added','field'=>null,'old_value'=>null,'new_value'=>null,'note'=>'Published menu update.','source_url'=>'https://www.mcdonalds.com/us/en-us/full-menu.html','observed_at'=>'2026-07-29 00:00:00'],$overrides);
    }
    public function test_all_forty_changes_import_and_repeat_sync_is_idempotent(): void {
        $records=collect(range(1,40))->map(fn($n)=>$this->record(['meal_name'=>'Burger '.$n,'meal_slug'=>'burger-'.$n]))->all();
        Http::preventStrayRequests();
        Http::fake(['healthyfastfood.org/*'=>Http::response(['changes'=>$records])]);
        $this->artisan('fastfood:sync')->assertSuccessful();
        $this->artisan('fastfood:sync')->assertSuccessful();
        $this->assertDatabaseCount('fast_food_changes',40);
        Http::assertSent(fn($r)=>$r->url()==='https://healthyfastfood.org/api/v1/changes?restaurant=mcdonalds&limit=40');
        $this->get('/fast-food')->assertOk()->assertSee('40 stored changes')->assertSee('Healthy Fast Food')->assertSee('/images/fast-food-still.png',false)->assertViewHas('changes',fn($changes)=>$changes->total()===40&&$changes->count()===20);
    }
    public function test_filters_and_source_text_are_escaped(): void {
        Http::fake(['*'=>Http::response(['changes'=>[$this->record(['meal_name'=>'<script>alert(1)</script>','change_type'=>'nutrition_changed','field'=>'calories','old_value'=>'540','new_value'=>'580']),$this->record(['meal_name'=>'Other item'])]])]);
        app(FastFoodFeed::class)->import();
        $this->get('/fast-food?type=nutrition_changed&q=alert')->assertOk()->assertSee('&lt;script&gt;',false)->assertDontSee('<script>alert(1)</script>',false)->assertSee('540')->assertSee('580')->assertViewHas('changes',fn($changes)=>$changes->total()===1);
        $this->get('/fast-food?type=invalid')->assertSessionHasErrors('type');
    }
    public function test_invalid_import_rolls_back_and_failed_api_preserves_records(): void {
        Http::fake(['*'=>Http::sequence()->push(['changes'=>[$this->record()]],200)->push(['changes'=>[$this->record(),$this->record(['id'=>'bad'])]],200)->push([],503)]);
        $this->artisan('fastfood:sync')->assertSuccessful();
        $this->artisan('fastfood:sync')->assertFailed();
        $this->assertDatabaseCount('fast_food_changes',1);
        $this->artisan('fastfood:sync')->assertFailed();
        $this->assertDatabaseCount('fast_food_changes',1);
    }
    public function test_house_fast_food_checkout_uses_local_prices(): void {
        $this->get('/')->assertOk()->assertSee('House cheeseburger')->assertSee('Fast food')->assertSee('/images/fast-food-still.png',false);
        $this->postJson('/orders',['customer'=>'Sokha','phone'=>'012345678','service'=>'Takeaway','items'=>[['id'=>9,'quantity'=>2,'price'=>1],['id'=>11,'quantity'=>1]],'total'=>1])->assertCreated()->assertJsonPath('total',1100);
        $this->assertDatabaseHas('orders',['total'=>1100]);
        $this->assertSame('GIF89a',substr(file_get_contents(public_path('images/fast-food-feature.gif')),0,6));
    }
}
