@once
<script>window.mapsConfig=Object.assign({latitude:11.5564,longitude:104.9282},window.mapsConfig||{},{google:@json((bool)config('delivery.browser_key')),mapId:@json(config('delivery.map_id'))});</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
<script src="/maps-kit.js?v=1"></script>
@if(config('delivery.browser_key'))<script async src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('delivery.browser_key')) }}&amp;loading=async&amp;callback=srokGoogleMapsReady&amp;v=weekly&amp;libraries=marker"></script>@endif
@endonce
