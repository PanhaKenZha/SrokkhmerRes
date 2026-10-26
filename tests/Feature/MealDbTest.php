<?php
namespace Tests\Feature;

use App\Models\Meal;
use App\Services\MealCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MealDbTest extends TestCase {
    use RefreshDatabase;
    private function recipe(array $overrides=[]): array {
        return array_merge(['idMeal'=>'52795','strMeal'=>'Chicken Handi','strCategory'=>'Chicken','strArea'=>'Indian','strMealThumb'=>'https://www.themealdb.com/images/test.jpg','strInstructions'=>'Cook carefully.','strIngredient1'=>'Chicken','strMeasure1'=>'1 kg','strIngredient2'=>' ','strYoutube'=>'javascript:alert(1)'],$overrides);
    }
    public function test_all_results_import_and_refresh_preserves_prices(): void {
        Http::preventStrayRequests();
        Http::fake(['www.themealdb.com/*'=>Http::response(['meals'=>[$this->recipe(),$this->recipe(['idMeal'=>'52800','strMeal'=>'Other chicken'])]])]);
        $this->artisan('meals:sync')->assertSuccessful();
        $this->assertDatabaseCount('meals',2);
        $meal=Meal::where('external_id',52795)->first();
        $this->assertSame([['name'=>'Chicken','measure'=>'1 kg']],$meal->ingredients);
        $this->assertNull($meal->youtube);
        $meal->update(['price'=>900]);
        $this->artisan('meals:sync')->assertSuccessful();
        $this->assertDatabaseCount('meals',2);
        $this->assertSame(900,$meal->fresh()->price);
        $this->get('/')->assertOk()->assertSee('Chicken Handi')->assertSee('Chicken');
        $id=collect(app(MealCatalog::class)->menu())->firstWhere('external_id',52795)['id'];
        $this->postJson('/orders',['customer'=>'Sokha','phone'=>'012345678','service'=>'Takeaway','items'=>[['id'=>$id,'quantity'=>2,'price'=>1]]])->assertCreated()->assertJsonPath('total',1800);
    }
    public function test_failed_or_empty_api_keeps_existing_menu(): void {
        Http::fake(['*'=>Http::sequence()->push(['meals'=>[$this->recipe()]])->push(['meals'=>null])->push([],503)]);
        app(MealCatalog::class)->import('chicken');
        $this->assertSame(0,app(MealCatalog::class)->import('missing'));
        $this->artisan('meals:sync')->assertFailed();
        $this->assertDatabaseCount('meals',1);
    }
    public function test_malformed_response_rolls_back_import(): void {
        Http::fake(['*'=>Http::response(['meals'=>[$this->recipe(),['strMeal'=>'Missing ID']]])]);
        $this->artisan('meals:sync')->assertFailed();
        $this->assertDatabaseCount('meals',0);
    }
}
