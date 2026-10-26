# Google restaurant maps and delivery

## Enable live Google services

1. In [Google Cloud Console](https://console.cloud.google.com/google/maps-apis/overview), create/select a project and attach billing.
2. Enable **Maps JavaScript API**, **Places API (New)** and **Routes API**.
3. Create a browser key restricted to Maps JavaScript API and your website's HTTP referrers. For local development include `http://127.0.0.1:8000/*` and/or `http://localhost:8000/*`.
4. Create a separate server key restricted to Places API (New), Routes API and your server's public outbound IP. Never use this key in browser code.
5. Set these values locally in `.env` (do not commit keys or paste them into chat):

```dotenv
GOOGLE_MAPS_BROWSER_KEY=
GOOGLE_MAPS_SERVER_KEY=
RESTAURANT_LATITUDE=
RESTAURANT_LONGITUDE=
DELIVERY_MAX_KM=15
DELIVERY_BASE_CENTS=100
DELIVERY_PER_KM_CENTS=30
```

Use your real kitchen's pickup coordinates. The map's default center is Phnom Penh; this is not a configured pickup location. Run `php artisan config:clear` after editing `.env`. Create a production Google map ID and replace the development `DEMO_MAP_ID` in `public/delivery.js` before launch. Configure API quotas and billing alerts in your own Google Cloud account.

## Restaurant discovery

Visit `/restaurants`, allow location access or pan to an area, choose a radius and press **Search this area**. Nearby Search (New) returns up to 20 restaurants per request; the map does not claim to contain every restaurant in Cambodia. Each listing opens Google Maps. These are discovery listings, not partner restaurants, and the app cannot accept orders for them. Google/provider attribution is retained. Results are fetched on demand without being stored in the application database.

## Delivery checkout

Choose **Delivery**, enter the written address and pin the exact entrance (or use device location / manual coordinates). Click **Calculate delivery fee**. The server calls Routes API for driving road distance and travel duration from the configured kitchen.

The fee in integer US cents is `base + ceil(distanceMeters / 1000) × per_km`. With the sample settings, 3.2 km costs $2.20. USD/KHR display uses the app's configured reference rate. Routes over `DELIVERY_MAX_KM` are rejected. Quote IDs are bound to the customer's session, expire after 10 minutes and are consumed when an order is placed. Server-stored fees, address and coordinates are authoritative; submitted totals cannot override them. Preparation time and rider wait are not included in Google's driving estimate.

Orders save address, drop-off coordinates, route metrics and delivery fee. Staff can assign a rider name/phone, open Google Maps driving directions, and set **Out for delivery** / **Delivered**. A rider name and phone are required to dispatch. The customer receives an unguessable order-status link that reveals no address, phone number or rider details. It updates on refresh, not with live GPS.

This creates a restaurant-managed delivery workflow, not a courier booking integration. You must arrange riders yourself. Payment is collected on delivery. There is no automatic dispatch, courier app, SMS or live vehicle tracking. User-provided written addresses are not geocoded or automatically matched to the pin, so staff should verify ambiguous addresses. Location permissions require HTTPS in production (localhost is suitable for development).

## Verification and limits

`php artisan test` covers nearby-search requests, missing configuration, route failures, maximum radius, price tampering, quote expiry/consumption, staff dispatch and private tracking. Live Google requests and browser map interaction require configured keys; mocked API tests do not establish that your billing, key restrictions or enabled APIs are correct. Publish your privacy policy and terms before launch, explaining location/address use and sharing with Google, and review Google's current terms.

Official references: [Nearby Search](https://developers.google.com/maps/documentation/places/web-service/nearby-search), [Routes computeRoutes](https://developers.google.com/maps/documentation/routes/reference/rest/v2/TopLevel/computeRoutes), [billing](https://developers.google.com/maps/documentation/places/web-service/usage-and-billing), [API key restrictions](https://developers.google.com/maps/api-security-best-practices).
