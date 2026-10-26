<?php
namespace App\Services;

use App\Models\Meal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MealCatalog {
    public function menu(?int $restaurantId=null): array {
        if($restaurantId!==null) return \App\Models\MenuItem::where('restaurant_id',$restaurantId)->where('is_available',true)->orderBy('name')->get()->map(fn($item)=>[
            'id'=>2000000+$item->id,'name'=>$item->name,'kh'=>$item->kh?:$item->name,'category'=>$item->category,
            'price'=>$item->price,'description'=>$item->description??'','image'=>$item->image,'tags'=>$item->tagLabels(),'prep_minutes'=>$item->prep_minutes,'emoji'=>'🍽️',
        ])->all();
        $houseMenu=collect(config('restaurant.menu'))->map(fn($dish)=>array_merge($dish,config('menu_photos.'.$dish['id'],[])))->all();
        return array_merge($houseMenu, Meal::orderBy('name')->get()->map(fn($meal)=>[
            'id'=>1000000+$meal->id, 'name'=>$meal->name, 'kh'=>$meal->name,
            'category'=>$meal->category, 'price'=>$meal->price, 'emoji'=>'🍽️',
            'description'=>implode(', ',array_column(array_slice($meal->ingredients,0,4),'name')),
            'image'=>$meal->image, 'area'=>$meal->area, 'ingredients'=>$meal->ingredients,
            'instructions'=>$meal->instructions, 'youtube'=>$meal->youtube, 'source'=>$meal->source,
            'external_id'=>$meal->external_id,
        ])->all());
    }

    public function import(string $search): int {
        $response=Http::acceptJson()->connectTimeout(10)->timeout(45)->get(
            'https://www.themealdb.com/api/json/v1/'.rawurlencode(config('restaurant.mealdb_key')).'/search.php',
            ['s'=>$search]
        )->throw();
        $payload=$response->json();
        if (!is_array($payload) || !array_key_exists('meals',$payload) || ($payload['meals']!==null && !is_array($payload['meals']))) {
            throw ValidationException::withMessages(['api'=>'TheMealDB returned an invalid response.']);
        }
        return DB::transaction(function () use ($payload) {
            $count=0;
            foreach ($payload['meals'] ?? [] as $raw) {
                if (!is_array($raw) || !ctype_digit((string)($raw['idMeal']??'')) || empty($raw['strMeal'])) {
                    throw ValidationException::withMessages(['api'=>'TheMealDB returned an invalid meal.']);
                }
                $ingredients=[];
                for ($i=1;$i<=20;$i++) {
                    $name=trim($raw['strIngredient'.$i]??'');
                    if ($name!=='') $ingredients[]=['name'=>$name,'measure'=>trim($raw['strMeasure'.$i]??'')];
                }
                $meal=Meal::firstOrNew(['external_id'=>(int)$raw['idMeal']]);
                if (!$meal->exists) $meal->price=config('restaurant.import_price');
                $meal->fill([
                    'name'=>$raw['strMeal'], 'category'=>$raw['strCategory']??'International',
                    'area'=>$raw['strArea']??null, 'image'=>$this->safeUrl($raw['strMealThumb']??null),
                    'instructions'=>$raw['strInstructions']??'', 'ingredients'=>$ingredients,
                    'youtube'=>$this->safeUrl($raw['strYoutube']??null), 'source'=>$this->safeUrl($raw['strSource']??null),
                ])->save();
                $count++;
            }
            return $count;
        });
    }

    private function safeUrl(?string $url): ?string {
        return filter_var($url,FILTER_VALIDATE_URL) && parse_url($url,PHP_URL_SCHEME)==='https' ? $url : null;
    }
}
