<?php
namespace App\Http\Controllers;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class RestaurantController extends Controller {
    public function menu(\App\Services\MealCatalog $catalog) {
        $restaurants=\App\Models\Restaurant::where('is_active',true)->orderBy('name')->get();
        return view('menu', ['menu'=>$catalog->menu(),'banners'=>\App\Models\Banner::live()->get(),'managedRestaurants'=>$restaurants,'locations'=>$restaurants->map->only(['id','name','address','latitude','longitude'])->values()]);
    }
    public function store(Request $request) {
        if(config('restaurant.order_requires_login') && !Auth::check()) {
            return response()->json(['message'=>'Please sign in or create an account to place your order.','login_url'=>'/login?return=%2F%23menu'],401);
        }
        $data=$request->validate([
            'customer'=>'required|string|max:100','phone'=>['required','regex:/^\+?[0-9 ()-]{8,20}$/'],
            'service'=>'required|in:Dine in,Takeaway,Delivery','table_number'=>'nullable|required_if:service,Dine in|integer|min:1|max:50',
            'quote_id'=>'nullable|required_if:service,Delivery|uuid',
            'restaurant_id'=>'nullable|integer|exists:restaurants,id',
            'notes'=>'nullable|string|max:500','items'=>'required|array|min:1|max:100',
            'items.*.id'=>'required|integer|distinct','items.*.quantity'=>'required|integer|min:1|max:20',
        ]);
        $restaurantId=isset($data['restaurant_id'])?(int)$data['restaurant_id']:null;
        if($restaurantId) abort_unless(\App\Models\Restaurant::whereKey($restaurantId)->where('is_active',true)->exists(),422,'Restaurant is unavailable.');
        $catalog=collect(app(\App\Services\MealCatalog::class)->menu($restaurantId))->keyBy('id');
        $items=collect($data['items'])->map(function ($line) use ($catalog) {
            abort_unless($catalog->has($line['id']),422,'Dish unavailable.');
            $dish=$catalog[$line['id']];
            return ['id'=>$dish['id'],'name'=>$dish['name'],'price'=>$dish['price'],'quantity'=>$line['quantity']];
        });
        $delivery=[];
        if($data['service']==='Delivery') {
            $quote=$request->session()->get('delivery_quote');
            if(!$quote || !hash_equals($quote['id'],$data['quote_id']) || $quote['expires']<now()->timestamp) {
                throw \Illuminate\Validation\ValidationException::withMessages(['quote_id'=>'Get a new delivery quote before placing your order.']);
            }
            $delivery=$quote['data'];
            if(($delivery['restaurant_id']??null)!==$restaurantId) throw \Illuminate\Validation\ValidationException::withMessages(['quote_id'=>'Get a quote for the selected restaurant.']);
        }
        $order=Order::create([...collect($data)->except(['items','quote_id'])->all(),...$delivery,'table_number'=>$data['service']==='Dine in'?($data['table_number']??null):null,'user_id'=>Auth::id(),'reference'=>(string) Str::uuid(),'items'=>$items->all(),'total'=>$items->sum(fn($i)=>$i['price']*$i['quantity'])+($delivery['delivery_fee']??0)]);
        if($data['service']==='Delivery') $request->session()->forget('delivery_quote');
        return response()->json(['reference'=>$order->reference,'total'=>$order->total,'tracking_url'=>'/orders/'.$order->reference],201);
    }
    public function login(Request $request) {
        $credentials=$request->validate(['email'=>'required|email','password'=>'required|string']);
        if (!Auth::attempt($credentials,$request->boolean('remember'))) {
            return back()->withErrors(['email'=>'The email or password is incorrect.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect()->intended(Auth::user()->isWorkspaceMember()?'/staff':'/');
    }
    public function dashboard(Request $request, \App\Services\ManagerDashboard $dashboard) {
        abort_unless(Auth::user()->hasPermission('orders.manage'),403);
        return view('staff',$dashboard->data($request));
    }
    public function update(Request $request, Order $order) {
        abort_unless(Auth::user()->hasPermission('orders.manage'),403);
        $data=$request->validate(['status'=>'required|in:New,Preparing,Ready,Completed,Cancelled,Out for delivery,Delivered','rider_name'=>'nullable|string|max:100','rider_phone'=>['nullable','regex:/^\+?[0-9 ()-]{8,20}$/']]);
        if(in_array($data['status'],['Out for delivery','Delivered'],true)) {
            abort_unless($order->service==='Delivery',422,'This is not a delivery order.');
        }
        if($data['status']==='Out for delivery' && (empty($data['rider_name'])||empty($data['rider_phone']))) {
            return back()->withErrors(['rider_name'=>'Enter the rider name and phone before dispatch.']);
        }
        $order->update($data);
        return back()->with('success','Order updated.');
    }
}
