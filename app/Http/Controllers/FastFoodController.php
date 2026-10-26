<?php
namespace App\Http\Controllers;
use App\Models\FastFoodChange;
use Illuminate\Http\Request;
class FastFoodController extends Controller {
    public function index(Request $request){
        $filters=$request->validate(['type'=>'nullable|in:added,removed,renamed,nutrition_changed,recategorized','q'=>'nullable|string|max:80']);
        $query=FastFoodChange::query();
        if(!empty($filters['type']))$query->where('change_type',$filters['type']);
        if(!empty($filters['q']))$query->where('meal_name','like','%'.trim($filters['q']).'%');
        return view('fast-food',[
            'changes'=>$query->orderByDesc('observed_at')->orderBy('meal_name')->orderBy('id')->paginate(20)->withQueryString(),
            'filters'=>$filters,'count'=>FastFoodChange::count(),
            'lastObserved'=>FastFoodChange::max('observed_at'),
            'lastSynced'=>FastFoodChange::max('updated_at'),
        ]);
    }
}
