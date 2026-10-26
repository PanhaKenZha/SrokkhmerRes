<?php
namespace App\Http\Controllers;
use App\Services\GoogleMaps;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class DeliveryController extends Controller {
    public function nearby(Request $request,GoogleMaps $maps) {
        $data=$request->validate(['latitude'=>'required|numeric|between:-90,90','longitude'=>'required|numeric|between:-180,180','radius'=>'required|integer|between:100,10000']);
        try {return response()->json(['places'=>$maps->nearby((float)$data['latitude'],(float)$data['longitude'],(int)$data['radius'])]);}
        catch(ConnectionException|RequestException $e){return response()->json(['message'=>'Restaurant search is unavailable. Check the Google API configuration or try again later.'],503);}
    }
    public function quote(Request $request,GoogleMaps $maps) {
        $data=$request->validate(['latitude'=>'required|numeric|between:-90,90','longitude'=>'required|numeric|between:-180,180','address'=>'required|string|max:500','restaurant_id'=>'nullable|integer|exists:restaurants,id']);
        $restaurant=empty($data['restaurant_id'])?null:\App\Models\Restaurant::findOrFail($data['restaurant_id']);
        if($restaurant) abort_unless($restaurant->is_active,422,'Restaurant is unavailable.');
        try {$quote=$maps->quote((float)$data['latitude'],(float)$data['longitude'],$restaurant);}
        catch(ConnectionException|RequestException $e){return response()->json(['message'=>'Delivery pricing is unavailable. Please try again or select takeaway.'],503);}
        if($restaurant)$quote['restaurant_id']=$restaurant->id;
        $id=(string)Str::uuid();
        $request->session()->put('delivery_quote',['id'=>$id,'expires'=>now()->addMinutes(10)->timestamp,'data'=>[...$quote,'delivery_address'=>$data['address']]]);
        return response()->json(['quote_id'=>$id,'fee'=>$quote['delivery_fee'],'distance_meters'=>$quote['delivery_distance_meters'],'duration_seconds'=>$quote['delivery_duration_seconds'],'expires_in'=>600]);
    }
}
