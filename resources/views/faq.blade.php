@extends('layout')
@section('content')
<main class="info-page"><section class="info-hero"><p class="eyebrow">A LITTLE HELP WITH YOUR NEXT MEAL</p><h1>Good questions.<br><em>Simple answers.</em></h1><p>Here’s how restaurant discovery, ordering, and delivery work.</p></section><section class="faq-list">@foreach([
    ['How do I place an order?','Choose a restaurant, add available dishes to your cart, and continue to checkout. Enter your details and choose dine-in, takeaway, or delivery.'],
    ['Can I order from every restaurant on the map?','Published restaurants with a menu on this website accept orders here. Google nearby listings are for discovery and do not automatically accept orders through Srok Khmer.'],
    ['How do delivery fees work?','Choose delivery, enter your address and select your drop-off location. Calculate the fee before confirming. Delivery availability and pricing depend on a valid road route and the restaurant’s delivery area.'],
    ['How do I pay?','Payment is collected at the restaurant or on delivery. Online card, ABA PayWay, and KHQR payments are not connected.'],
    ['Where can I track my order?','Use the tracking link shown after your order is confirmed. Refresh that page to see the latest status updated by the restaurant. It does not show live rider GPS.'],
    ['Can I change or cancel an order?','Contact the restaurant directly and provide your order reference. The restaurant will confirm whether a change or cancellation is still possible.'],
    ['Can I order dishes from multiple restaurants together?','Each order belongs to one restaurant. Place separate orders when ordering from different restaurants.'],
    ['Are menu photos and prices final?','This project includes sample dishes, sample prices, and illustrative API recipe photos. Check the restaurant’s published information and contact it to confirm details before ordering.'],
] as [$question,$answer])<details><summary>{{ $question }}<span aria-hidden="true">+</span></summary><p>{{ $answer }}</p></details>@endforeach</section><section class="info-callout"><div><h2>Still have a question?</h2><p>Find the right restaurant and get in touch.</p></div><a class="button" href="/contact">Contact us ↗</a></section></main>
@endsection
