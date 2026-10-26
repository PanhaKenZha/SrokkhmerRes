<section id="delivery-fields" hidden>
<label>Delivery address<textarea name="delivery_address" id="delivery-address" maxlength="500" placeholder="House, street, sangkat, city and landmark"></textarea></label>
<p class="muted">Enter your address and pin the exact entrance on the map, or use your current location. Delivery is from our kitchen only.</p>
<button type="button" class="category" id="delivery-locate">Use my location</button>
<div id="delivery-map" aria-label="Choose delivery location"></div>
<div class="form-row"><label>Latitude<input id="delivery-latitude" type="number" step="any" min="-90" max="90"></label><label>Longitude<input id="delivery-longitude" type="number" step="any" min="-180" max="180"></label></div>
<button type="button" class="category" id="quote-delivery">Calculate delivery fee</button>
<p id="delivery-quote-message" role="status">Get a delivery quote before confirming your order.</p><input type="hidden" name="quote_id" id="delivery-quote-id">
<p class="muted">Road travel time is an estimate, excluding kitchen preparation and rider availability. Pay on delivery.</p>
</section>
<p id="checkout-total" class="checkout-total" aria-live="polite"></p>
