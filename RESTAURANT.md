# Srok Khmer restaurant

A Laravel 13 restaurant starter for Cambodia, built with Blade, CSS/JavaScript and SQLite. No Node build is needed.

## Run locally

```powershell
cd C:\Users\USER\Desktop\resteruant\restaurant
php artisan serve
```

Open http://127.0.0.1:8000. Dependencies and the local database are installed. Create a staff login:

```powershell
php artisan restaurant:manager
```

Choose a password when prompted. The manager email defaults to `manager@example.com`; set `STAFF_EMAIL` in `.env` before creating the account to use your own email. Sign in at `/staff`. No default password is included.

## Features and configuration

### Add restaurants, menus and locations

Sign in as manager, then open **Restaurants & locations** (`/staff/restaurants`). Enter name, phone, description and address; click the live map (or enter latitude/longitude) to pin the entrance. Enable **Publish** and save. You can edit or unpublish restaurants later.

Open **Add menu items** (`/staff/menu`), choose a restaurant, add English/Khmer names, category, USD price, description and an optional direct HTTPS photo URL. Available dishes appear on that restaurant's public menu. Prices are stored as integer cents. Disable availability to remove a dish from checkout. Existing demo/API menus remain on the main house page; managed items belong to their selected restaurant.

Published restaurants appear on the homepage map and `/restaurants`. Each has a dedicated `/restaurants/{id}` menu and location map with directions. Leaflet/OpenStreetMap displays real map tiles without a Google API key; maps need internet access to load the Leaflet CDN and tiles. Attribution is included. For high-traffic production use, configure an appropriate hosted tile service and review [OpenStreetMap tile usage](https://operations.osmfoundation.org/policies/tiles/).

Orders are bound to one restaurant; dishes from another restaurant cannot be mixed into the order. Staff see the restaurant name on each order. Delivery quotes use that restaurant's saved pickup coordinates and remain bound to it. Google Routes keys/billing are still required for road-distance quotes. No separate restaurant-owner accounts are created; the configured manager manages all restaurants.

- Khmer/English dish names, categories, search, cart and USD/KHR prices.
- Dine-in with table number, takeaway, kitchen notes and persistent orders.
- Staff login, order dashboard and status updates.
- Dedicated manager workspace at `/staff`, with reporting periods, completed-order sales, a seven-day sales chart, service breakdown, active delivery counts, searchable/filterable order queue and expandable order/rider controls. Metrics use the reporting period; queue filters apply only to the order list. Sales include delivery fees and fulfilled orders only, without claiming verified payments. Use Refresh to load new orders; unsaved edits are not automatically refreshed.
- Server-calculated integer-cent totals, validation, CSRF protection and throttling.
- Asia/Phnom_Penh timezone.

Edit dishes and prices in `config/restaurant.php`. Set `KHR_PER_USD=4100` in `.env` to customize the example reference rate; this is not a live feed. Dish names switch languages; the remaining interface is English. Local dishes use emoji illustrations; imported meals use TheMealDB photos. Cart contents reset when the page reloads.

Payment is collected at the restaurant or on delivery. ABA PayWay, KHQR/Bakong verification, inventory, dashboard menu editing and tax accounting are not integrated. Replace sample branding and kitchen-open text before launch.

Google restaurant discovery, delivery quotes, staff rider assignment and customer order-status tracking are implemented. Live maps and quotes require Google API keys, billing and real kitchen coordinates: see [GOOGLE_MAPS_DELIVERY.md](GOOGLE_MAPS_DELIVERY.md). Restaurant discovery lists nearby Google results, not all restaurants or ordering partners. Delivery is managed by your staff; automatic courier booking and live rider GPS are not included.

## TheMealDB integration

## Fast food and animated artwork

The supplied 900×900, 24-frame GIF is stored unchanged at `public/images/fast-food-feature.gif` and used in the fast-food feature banner on the homepage and `/fast-food`. A still frame, reduced-motion preference and Play/Pause control are included.

Six house fast-food dishes (IDs 9–14) are in `config/restaurant.php`, with Khmer names and sample local prices. These are your restaurant's sample dishes, not McDonald's products. They use the existing cart, server-side price calculation, order dashboard and delivery checkout. The banner button selects the Fast food category.

House fast-food cards request TheMealDB directly from the browser on page load. `public/food-photos.js` searches for the recipe names mapped in `config/menu_photos.php`, reads `strMealThumb`, and displays that remote image URL. No fast-food photos are downloaded or saved by this flow; previous local JPGs are unused. Repeated recipes share one request per page load. Requests time out after 12 seconds and keep the food illustration on failure. Captions identify the source recipes as sample imagery, not photographs of the actual house dishes (for example, the fries reference is Baked Yuca Fries). Replace these with your own product photos before selling the actual menu. Local image URLs are supported, and failed photo loads reveal the existing food illustration. The Healthy Fast Food change endpoint itself provides no product-image field.

`php artisan fastfood:sync` fetches the supplied `https://healthyfastfood.org/api/v1/changes?restaurant=mcdonalds&limit=40` endpoint. It stores every returned change by its external ID, avoids duplicates on repeated syncs and rolls back malformed responses. API failures preserve existing records. `/fast-food` displays a paginated, searchable feed with change-type filters, provider observation dates, reported before/after values, published-menu source links and attribution to [Healthy Fast Food](https://healthyfastfood.org).

This API is a historical change feed, not a complete live menu, product-photo service, pricing feed or ordering integration. Its US chain menu records do not establish Cambodian availability. Feed records cannot be added to the cart. Normal browsing reads the local database without requiring an external API request. Refresh manually with the sync command.

### TheMealDB recipe import

The menu includes all meals imported from the supplied chicken search. Refresh the database using `php artisan meals:sync`, or import another search using `php artisan meals:sync --search=beef`. All returned meals are imported without truncation; this endpoint does not return the entire TheMealDB database. Existing local dishes remain available.

Photos, category, origin, ingredient measurements, instructions and available video/source links appear in the menu and recipe dialog. Imported names remain in the original language. Imported dishes are orderable using local prices, calculated on the server. Set `MEALDB_DEFAULT_PRICE_CENTS=650` for new imports; the API supplies no prices. Existing prices are preserved on refresh. To change one, run `php artisan tinker` and use `App\Models\Meal::where('external_id', 52795)->update(['price' => 750]);` (integer USD cents).

Imports are stored in SQLite, so normal browsing and checkout do not depend on the external API. Failed imports roll back and preserve existing data. Empty searches add no dishes. API key `1` is for development/education; configure `MEALDB_API_KEY` for your own key. See [TheMealDB API documentation](https://www.themealdb.com/api.php) for access terms. Recipe photos load from TheMealDB.

### Verification commands

```powershell
php artisan test
php artisan view:cache
node --check public/restaurant.js
```

## Fresh installation and hosting

Requires PHP 8.3+, Composer and PDO SQLite. Run `composer install`, copy `.env.example` to `.env`, run `php artisan key:generate`, create `database/database.sqlite`, then `php artisan migrate`.

Use PHP hosting with document root `public/`, HTTPS, persistent database storage, writable `storage/` and `bootstrap/cache/`, `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, the correct `APP_URL` and a strong manager password. Run `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force` and `php artisan optimize`. Back up the database and protect `.env`. Multiple application servers need shared database/session storage. Fonts load from Google Fonts with local fallbacks. Sites Cloudflare Workers hosting cannot run this PHP application.
