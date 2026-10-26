<?php
namespace App\Http\Controllers;
use App\Models\Restaurant;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class ManagementController extends Controller {
    private function authorizeManager(string $permission): void {abort_unless(auth()->user()?->hasPermission($permission),403);}
    private function like(string $term): string {return '%'.addcslashes($term,'%_\\').'%';}
    private function restaurantPage(Request $request,?Restaurant $editing){
        $listFilters=['q'=>trim((string)$request->query('q','')),'status'=>in_array($request->query('status'),['published','hidden'],true)?$request->query('status'):''];
        $restaurants=Restaurant::withCount('menuItems')
            ->when($listFilters['q']!=='',fn($query)=>$query->where(fn($match)=>$match->where('name','like',$this->like($listFilters['q']))->orWhere('address','like',$this->like($listFilters['q']))->orWhere('phone','like',$this->like($listFilters['q']))))
            ->when($listFilters['status']!=='',fn($query)=>$query->where('is_active',$listFilters['status']==='published'))
            ->latest()->paginate(12)->withQueryString();
        $stats=['total'=>Restaurant::count(),'live'=>Restaurant::where('is_active',true)->count(),'items'=>MenuItem::count()];
        return view('manage-restaurants',compact('restaurants','editing','listFilters','stats'));
    }
    public function restaurants(Request $request){
        $this->authorizeManager('restaurants.manage');
        return $this->restaurantPage($request,null);
    }
    public function editRestaurant(Request $request,Restaurant $restaurant){
        $this->authorizeManager('restaurants.manage');
        return $this->restaurantPage($request,$restaurant);
    }
    public function saveRestaurant(Request $request,?Restaurant $restaurant=null){
        $this->authorizeManager('restaurants.manage');
        $data=$request->validate(['name'=>'required|string|max:120','description'=>'nullable|string|max:2000','phone'=>['required','regex:/^\+?[0-9 ()-]{8,20}$/'],'address'=>'required|string|max:500','latitude'=>'required|numeric|between:-90,90','longitude'=>'required|numeric|between:-180,180','is_active'=>'nullable|boolean']);
        $data['is_active']=$request->boolean('is_active');
        ($restaurant??new Restaurant)->fill($data)->save();
        return redirect('/staff/restaurants')->with('success','Restaurant saved. Published restaurants are visible on the customer map.');
    }
    public function menu(Request $request,?MenuItem $item=null){
        $this->authorizeManager('menu.manage');
        $listFilters=['q'=>trim((string)$request->query('q','')),'status'=>in_array($request->query('status'),['available','unavailable'],true)?$request->query('status'):'','place'=>ctype_digit((string)$request->query('place'))?(string)$request->query('place'):''];
        $items=MenuItem::with('restaurant')
            ->when($listFilters['q']!=='',fn($query)=>$query->where(fn($match)=>$match->where('name','like',$this->like($listFilters['q']))->orWhere('kh','like',$this->like($listFilters['q']))->orWhere('category','like',$this->like($listFilters['q']))))
            ->when($listFilters['status']!=='',fn($query)=>$query->where('is_available',$listFilters['status']==='available'))
            ->when($listFilters['place']!=='',fn($query)=>$query->where('restaurant_id',$listFilters['place']))
            ->latest()->paginate(15)->withQueryString();
        $stats=['total'=>MenuItem::count(),'live'=>MenuItem::where('is_available',true)->count(),'average'=>(int)round(MenuItem::avg('price')??0)];
        $categories=collect(MenuItem::CATEGORIES)->merge(MenuItem::query()->distinct()->orderBy('category')->pluck('category'))->unique(fn($name)=>mb_strtolower($name))->values();
        return view('manage-menu',['restaurants'=>Restaurant::orderBy('name')->get(),'items'=>$items,'editing'=>$item,'listFilters'=>$listFilters,'stats'=>$stats,'categories'=>$categories]);
    }
    public function saveMenu(Request $request,?MenuItem $item=null){
        $this->authorizeManager('menu.manage');
        $data=$request->validate(['restaurant_id'=>'required|exists:restaurants,id','name'=>'required|string|max:120','kh'=>'nullable|string|max:120','category'=>'required|string|max:80','description'=>'nullable|string|max:1000','image'=>'nullable|url:https|max:2000','price_usd'=>['required','numeric','min:0.01','max:9999.99','regex:/^\d+(\.\d{1,2})?$/'],'is_available'=>'nullable|boolean','prep_minutes'=>'nullable|integer|min:1|max:240','tags'=>'nullable|array','tags.*'=>['string',\Illuminate\Validation\Rule::in(array_keys(MenuItem::TAGS))]]);
        $data['price']=(int)round((float)$data['price_usd']*100);unset($data['price_usd']);
        $data['is_available']=$request->boolean('is_available');
        $data['tags']=array_values(array_unique($data['tags']??[]));
        ($item??new MenuItem)->fill($data)->save();
        if(!$item&&$request->input('next')==='another') return redirect('/staff/menu?'.http_build_query(['restaurant_id'=>$data['restaurant_id'],'category'=>$data['category']]).'#management-editor')->with('success','Saved "'.$data['name'].'". Add the next dish.');
        return redirect('/staff/menu')->with('success','Menu item saved. Available items appear on their restaurant menu.');
    }
    public function directory(){
        $restaurants=Restaurant::where('is_active',true)->orderBy('name')->get();
        return view('restaurants',['managedRestaurants'=>$restaurants,'locations'=>$restaurants->map->only(['id','name','address','latitude','longitude'])->values()]);
    }
    public function show(Restaurant $restaurant,\App\Services\MealCatalog $catalog){
        abort_unless($restaurant->is_active,404);
        return view('menu',['restaurant'=>$restaurant,'menu'=>$catalog->menu($restaurant->id)]);
    }
}
