<script>window.mapsConfig=Object.assign(window.mapsConfig||{},{latitude:{{ is_numeric(($restaurant->latitude ?? config('delivery.latitude'))) ? (float)($restaurant->latitude ?? config('delivery.latitude')) : 11.5564 }},longitude:{{ is_numeric(($restaurant->longitude ?? config('delivery.longitude'))) ? (float)($restaurant->longitude ?? config('delivery.longitude')) : 104.9282 }}});</script>
@include('map-engine')
<script src="/delivery.js?v=2"></script>
