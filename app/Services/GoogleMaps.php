<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleMaps {
    private function client(string $fields) {
        abort_unless(config('delivery.server_key'),503,'Google Maps is not configured. Ask the restaurant to enable delivery.');
        return Http::acceptJson()->connectTimeout(5)->timeout(15)->withHeaders([
            'X-Goog-Api-Key'=>config('delivery.server_key'),'X-Goog-FieldMask'=>$fields,
        ]);
    }
    public function nearby(float $latitude,float $longitude,int $radius): array {
        $response=$this->client('places.id,places.displayName,places.formattedAddress,places.location,places.googleMapsUri,places.attributions')->post('https://places.googleapis.com/v1/places:searchNearby',[
            'includedTypes'=>['restaurant'],'maxResultCount'=>20,'rankPreference'=>'DISTANCE',
            'locationRestriction'=>['circle'=>['center'=>['latitude'=>$latitude,'longitude'=>$longitude],'radius'=>$radius]],
        ])->throw();
        $places=$response->json('places',[]);
        abort_unless(is_array($places),503,'Google returned an invalid restaurant response.');
        return $places;
    }
    public function quote(float $latitude,float $longitude,?\App\Models\Restaurant $restaurant=null): array {
        $originLat=$restaurant?->latitude??config('delivery.latitude');$originLng=$restaurant?->longitude??config('delivery.longitude');
        abort_unless(is_numeric($originLat)&&is_numeric($originLng)&&abs((float)$originLat)<=90&&abs((float)$originLng)<=180,503,'The restaurant pickup location has not been configured.');
        $response=$this->client('routes.distanceMeters,routes.duration')->post('https://routes.googleapis.com/directions/v2:computeRoutes',[
            'origin'=>['location'=>['latLng'=>['latitude'=>(float)$originLat,'longitude'=>(float)$originLng]]],
            'destination'=>['location'=>['latLng'=>['latitude'=>$latitude,'longitude'=>$longitude]]],
            'travelMode'=>'DRIVE','routingPreference'=>'TRAFFIC_UNAWARE',
        ])->throw();
        $route=$response->json('routes.0');
        if (!is_array($route)||!isset($route['distanceMeters'],$route['duration'])||!is_numeric($route['distanceMeters'])||!preg_match('/^([0-9]+(?:\.[0-9]+)?)s$/',(string)$route['duration'],$duration)) {
            throw ValidationException::withMessages(['destination'=>'No road route was found. Choose another delivery location.']);
        }
        $meters=(int)$route['distanceMeters'];
        if ($meters<0||$meters>config('delivery.max_km')*1000) {
            throw ValidationException::withMessages(['destination'=>'This location is outside our delivery area ('.config('delivery.max_km').' km by road).']);
        }
        return [
            'delivery_latitude'=>$latitude,'delivery_longitude'=>$longitude,
            'delivery_distance_meters'=>$meters,'delivery_duration_seconds'=>(int)ceil((float)$duration[1]),
            'delivery_fee'=>max(0,config('delivery.base_cents'))+(int)ceil($meters/1000)*max(0,config('delivery.per_km_cents')),
        ];
    }
}
