@extends('layout')
@section('content')
<main class="login-wrap"><div class="login-card"><p class="eyebrow">YOUR ORDER</p><h1>{{ $order->status }}</h1><p>Reference: {{ $order->reference }}</p><p>{{ $order->service }} · Total ${{ number_format($order->total/100,2) }}</p>@if($order->service==='Delivery')<p>Delivery fee: ${{ number_format($order->delivery_fee/100,2) }}</p><p>The restaurant updates your status as your order is prepared and delivered. Refresh to see changes.</p>@endif<p class="muted">Last updated {{ $order->updated_at->format('d M H:i') }} · Cambodia time</p><button class="button" onclick="location.reload()">Refresh status</button><p><a href="/">Back to menu</a></p></div></main>
@endsection
