@if($order->service==='Delivery')
<div class="delivery-summary"><p><strong>Deliver to:</strong> {{ $order->delivery_address }}</p><p>{{ number_format($order->delivery_distance_meters/1000,1) }} km by road · Delivery fee ${{ number_format($order->delivery_fee/100,2) }}</p><a href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $order->delivery_latitude }},{{ $order->delivery_longitude }}&amp;travelmode=driving" target="_blank" rel="noopener noreferrer">Open delivery directions ↗</a></div>
@endif
