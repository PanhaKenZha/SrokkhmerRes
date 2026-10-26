<?php
return [
    'browser_key'=>env('GOOGLE_MAPS_BROWSER_KEY'),
    'server_key'=>env('GOOGLE_MAPS_SERVER_KEY'),
    // Google map style ID from Cloud Console (Map Management). DEMO_MAP_ID is Google's development-only ID.
    'map_id'=>env('GOOGLE_MAPS_MAP_ID','DEMO_MAP_ID'),
    'latitude'=>env('RESTAURANT_LATITUDE'),
    'longitude'=>env('RESTAURANT_LONGITUDE'),
    'max_km'=>(float) env('DELIVERY_MAX_KM',15),
    'base_cents'=>(int) env('DELIVERY_BASE_CENTS',100),
    'per_km_cents'=>(int) env('DELIVERY_PER_KM_CENTS',30),
];
