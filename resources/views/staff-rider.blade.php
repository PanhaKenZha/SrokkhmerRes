@if($order->service==='Delivery')
<label>Rider name<input name="rider_name" maxlength="100" value="{{ $order->rider_name }}"></label><label>Rider phone<input name="rider_phone" type="tel" maxlength="20" value="{{ $order->rider_phone }}"></label>
@endif
