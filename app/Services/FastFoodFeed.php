<?php
namespace App\Services;
use App\Models\FastFoodChange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FastFoodFeed {
    public function import(): int {
        $payload=Http::acceptJson()->connectTimeout(10)->timeout(45)->get('https://healthyfastfood.org/api/v1/changes',['restaurant'=>'mcdonalds','limit'=>40])->throw()->json();
        if(!is_array($payload)||!isset($payload['changes'])||!is_array($payload['changes'])) {
            throw ValidationException::withMessages(['api'=>'Invalid fast-food response.']);
        }
        return DB::transaction(function() use($payload){
            foreach($payload['changes'] as $record){
                if(!is_array($record)) throw ValidationException::withMessages(['api'=>'Invalid fast-food record.']);
                $data=Validator::make($record,[
                    'id'=>'required|uuid','restaurant_slug'=>'required|in:mcdonalds','restaurant_name'=>'required|string|max:255',
                    'meal_name'=>'required|string|max:255','meal_slug'=>'required|string|max:255',
                    'change_type'=>'required|in:added,removed,renamed,nutrition_changed,recategorized',
                    'field'=>'nullable|string|max:255','old_value'=>'nullable|string|max:10000','new_value'=>'nullable|string|max:10000',
                    'note'=>'nullable|string|max:10000','source_url'=>'nullable|url:https|max:2000','observed_at'=>'required|date_format:Y-m-d H:i:s',
                ])->validate();
                $id=$data['id'];unset($data['id']);
                FastFoodChange::updateOrCreate(['external_id'=>$id],$data);
            }
            return count($payload['changes']);
        });
    }
}
