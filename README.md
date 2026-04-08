# UCubix PHP Client

PHP client for the [UCubix Distribution API](https://ucubix.com) with built-in rate limiting, typed DTOs, and full endpoint coverage.

## Requirements

- PHP 8.2+
- Guzzle 7.8+

## Installation

```bash
composer require ucubix/php-client
```

## Quick Start

```php
use Ucubix\PhpClient\Client\UcubixClient;

$client = new UcubixClient(apiKey: 'YOUR_API_KEY');

// Find a product
$products = $client->getProducts(['search' => 'Cyberpunk']);
$product = $client->getProduct($products->data[0]->id);

// Check regional pricing
foreach ($product->regional_pricing as $region) {
    echo "{$region->region_code} ({$region->reseller_wsp}% WSP)\n";
    foreach ($region->countries as $country) {
        echo "  {$country->country_name}: {$country->price} {$country->currency_code}\n";
    }
}

// Create an order
$order = $client->createOrder(
    productUuid: $product->id,
    quantity: 1,
    regionCode: $product->regional_pricing[0]->region_code,
    countryCode: $product->regional_pricing[0]->countries[0]->country_code,
);

echo "Order {$order->id} — status: {$order->status}\n";

// Get license keys when order is fulfilled
$items = $client->getOrderItems($order->id);
foreach ($items->data as $item) {
    if ($item->hasLicenseKey()) {
        $key = $client->getLicenseKey($item->license_key_uuid);
        echo "Key: {$key->license_key}\n";
    }
}
```

## Configuration

```php
$client = new UcubixClient(
    apiKey: 'YOUR_API_KEY',
    baseUrl: 'https://ucubix.com/api/v1/',  // default
);
```

---

## API Methods

### Organisation Info

| Method | Returns |
|---|---|
| `getOrganisation()` | [`Organisation`](#organisation) |

```php
$org = $client->getOrganisation();
echo $org->summary->total_usd_equivalent;
```

### Products

| Method | Returns |
|---|---|
| `getProducts(filters, page, perPage, sort)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Product`](#product)`>` |
| `getProduct(id)` | [`Product`](#product) |
| `getProductPhotos(id, page, perPage)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Media`](#media)`>` |
| `getProductScreenshots(id, page, perPage)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Media`](#media)`>` |
| `getProductCategories(id, page, perPage)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Category`](#category)`>` |
| `getProductPublishers(id, page, perPage)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Publisher`](#publisher--developer)`>` |
| `getProductPlatforms(id, page, perPage)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Platform`](#platform)`>` |
| `getProductFranchises(id, page, perPage)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Franchise`](#franchise)`>` |
| `getProductDevelopers(id, page, perPage)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Developer`](#publisher--developer)`>` |

**`getProducts()` filters** (validated, throws `InvalidArgumentException` on unknown keys):

| Key | Type | Description |
|---|---|---|
| `search` | `string` | Full-text search |
| `category` | `string` | Category UUID |
| `publisher` | `string` | Publisher UUID |
| `developer` | `string` | Developer UUID |
| `franchise` | `string` | Franchise UUID |
| `platform` | `string` | Platform UUID |

**Sort options:** `name`, `-name`, `created_at`, `-created_at`

```php
$products = $client->getProducts(
    filters: ['search' => 'Game', 'platform' => 'platform-uuid'],
    page: 1,
    perPage: 15,
    sort: 'name',
);

$product = $client->getProduct('product-uuid');
$photos  = $client->getProductPhotos('product-uuid');
```

### Orders

| Method | Returns |
|---|---|
| `getOrders(filters, page, perPage, sort)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Order`](#order)`>` |
| `getOrder(id)` | [`Order`](#order) |
| `getOrderItems(orderId, page, perPage)` | [`PaginatedResponse`](#paginatedresponse)`<`[`OrderItem`](#orderitem)`>` |
| `createOrder(productUuid, quantity, regionCode, countryCode?)` | [`Order`](#order) |
| `updateOrder(id, quantity)` | [`Order`](#order) |
| `cancelOrder(id)` | `bool` |

**`getOrders()` filters** (validated, throws `InvalidArgumentException` on unknown keys):

| Key | Type | Description |
|---|---|---|
| `code` | `string` | Filter by order code |
| `external_reference` | `string` | Filter by external reference |

**Sort options** (default: `-order_date`): `code`, `status`, `total_price`, `srp`, `currency_code`, `order_date`, `approved_at`, `rejected_at`, `delivered_at`, `distribution_model`

```php
$orders = $client->getOrders(filters: ['code' => 'ORD-001'], sort: '-order_date');
$order  = $client->getOrder('order-uuid');
$items  = $client->getOrderItems('order-uuid');

$order = $client->createOrder('product-uuid', 5, 'NorthAmerica', 'us');
$order = $client->updateOrder('order-uuid', quantity: 10);
$client->cancelOrder('order-uuid');
```

### License Keys

| Method | Returns |
|---|---|
| `getLicenseKey(id)` | [`LicenseKey`](#licensekey) |
| `getBulkLicenseKeys(ids)` | [`LicenseKey[]`](#licensekey) |

```php
$key  = $client->getLicenseKey('license-key-uuid');
$keys = $client->getBulkLicenseKeys(['uuid-1', 'uuid-2']); // up to 1000
```

### Catalog Dictionaries

| Method | Returns |
|---|---|
| `getCategories(page, perPage, sort)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Category`](#category)`>` |
| `getPublishers(page, perPage, sort)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Publisher`](#publisher--developer)`>` |
| `getPlatforms(page, perPage, sort)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Platform`](#platform)`>` |
| `getDevelopers(page, perPage, sort)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Developer`](#publisher--developer)`>` |
| `getFranchises(page, perPage, sort)` | [`PaginatedResponse`](#paginatedresponse)`<`[`Franchise`](#franchise)`>` |

```php
$categories = $client->getCategories(sort: 'name');
$publishers = $client->getPublishers();
$platforms  = $client->getPlatforms();
$developers = $client->getDevelopers();
$franchises = $client->getFranchises();
```

---

## Pagination

All list endpoints return [`PaginatedResponse`](#paginatedresponse)`<T>`:

```php
$page = 1;
do {
    $orders = $client->getOrders(page: $page, perPage: 50);

    foreach ($orders->data as $order) {
        // process order
    }

    $page++;
} while ($orders->hasMorePages());
```

---

## DTOs

All DTOs extend `Spatie\LaravelData\Data`. Properties are `readonly`.

### Product

| Property | Type | Notes |
|---|---|---|
| `id` | `string` | UUID |
| `name` | `string` | |
| `summary` | `?string` | |
| `description` | `?string` | |
| `release_date` | `?string` | ISO 8601 |
| `type` | `?string` | e.g. "Game" |
| `created_at` | `?string` | ISO 8601 |
| `regional_pricing` | [`RegionalPricing[]`](#regionalpricing) | Only on single-resource requests |
| `metadata` | [`?ProductMetadata`](#productmetadata) | System requirements, SteamDB |

### RegionalPricing

| Property | Type |
|---|---|
| `region_code` | `string` |
| `reseller_wsp` | `float` |
| `countries` | [`CountryPrice[]`](#countryprice) |

### CountryPrice

| Property | Type |
|---|---|
| `country_name` | `string` |
| `country_code` | `string` |
| `price` | `?float` |
| `currency_code` | `?string` |
| `is_promotion` | `bool` |
| `original_price` | `?float` |
| `promotion_name` | `?string` |
| `promotion_end_date` | `?string` |
| `can_be_ordered` | `bool` |
| `in_stock` | `bool` |

### ProductMetadata

| Property | Type |
|---|---|
| `minimum` | [`SystemRequirement[]`](#systemrequirement) |
| `recommended` | [`SystemRequirement[]`](#systemrequirement) |
| `steamdb` | [`?SteamdbInfo`](#steamdbinfo) |

### SystemRequirement

| Property | Type |
|---|---|
| `parameter` | `?string` |
| `value` | `?string` |

### SteamdbInfo

| Property | Type |
|---|---|
| `id` | `int` |
| `type` | `string` |
| `url` | `string` |

### Order

| Property | Type | Notes |
|---|---|---|
| `id` | `string` | UUID |
| `code` | `string` | |
| `external_reference` | `?string` | |
| `external_reference_attempt` | `?int` | |
| `status` | `string` | See [`OrderStatus`](#orderstatus) |
| `total_price` | `float` | |
| `srp` | `float` | |
| `estimated_cost` | `?float` | |
| `items_count` | `int` | |
| `currency_code` | `?string` | ISO 4217 |
| `order_date` | `string` | ISO 8601 |
| `approved_at` | `?string` | |
| `rejected_at` | `?string` | |
| `delivered_at` | `?string` | |
| `distribution_model` | `?string` | "sale", "consignment" |
| `rejection_note` | `?string` | |

Helper: `$order->getStatus()` returns [`OrderStatus`](#orderstatus) enum.

### OrderItem

| Property | Type | Notes |
|---|---|---|
| `id` | `string` | UUID |
| `price` | `float` | |
| `country_code` | `?string` | ISO 3166-1 alpha-2 |
| `license_key_uuid` | `?string` | Only when order is fulfilled/delivered |
| `fulfilled_at` | `?string` | |
| `created_at` | `?string` | |
| `updated_at` | `?string` | |

Helper: `$item->hasLicenseKey()` returns `bool`.

### LicenseKey

| Property | Type |
|---|---|
| `id` | `string` |
| `license_key` | `string` |
| `created_at` | `?string` |
| `updated_at` | `?string` |

### Organisation

| Property | Type |
|---|---|
| `uuid` | `string` |
| `name` | `string` |
| `summary` | [`OrganisationSummary`](#organisationsummary) |
| `credit_lines` | [`CreditLine[]`](#creditline) |

### OrganisationSummary

| Property | Type |
|---|---|
| `currencies` | `int` |
| `total_usd_equivalent` | `string` |

### CreditLine

| Property | Type |
|---|---|
| `currency` | `string` |
| `balance` | `string` |

### Category

| Property | Type |
|---|---|
| `id` | `string` |
| `name` | `string` |
| `parent_id` | `?string` |
| `child_ids` | `string[]` |

### Publisher / Developer

| Property | Type |
|---|---|
| `id` | `string` |
| `name` | `string` |
| `website` | `?string` |
| `about` | `?string` |
| `created_at` | `?string` |
| `updated_at` | `?string` |

### Platform

| Property | Type |
|---|---|
| `id` | `string` |
| `name` | `string` |
| `created_at` | `?string` |
| `updated_at` | `?string` |

### Franchise

| Property | Type |
|---|---|
| `id` | `string` |
| `name` | `string` |
| `created_at` | `?string` |

### Media

| Property | Type |
|---|---|
| `id` | `string` |
| `name` | `string` |
| `file_name` | `string` |
| `collection_name` | `string` |
| `mime_type` | `string` |
| `disk` | `string` |
| `size` | `int` |
| `order_column` | `?int` |
| `url` | `string` |
| `created_at` | `?string` |
| `updated_at` | `?string` |

### PaginatedResponse

| Property | Type |
|---|---|
| `data` | `T[]` |
| `currentPage` | `int` |
| `perPage` | `int` |
| `total` | `int` |
| `lastPage` | `int` |
| `firstPageUrl` | `?string` |
| `lastPageUrl` | `?string` |
| `nextPageUrl` | `?string` |
| `prevPageUrl` | `?string` |

Helper: `$response->hasMorePages()` returns `bool`.

---

## Enums

### OrderStatus

```php
use Ucubix\PhpClient\Enums\OrderStatus;

OrderStatus::NEW;        // 'new'
OrderStatus::PENDING;    // 'pending'
OrderStatus::APPROVED;   // 'approved'
OrderStatus::REJECTED;   // 'rejected'
OrderStatus::FULFILLED;  // 'fulfilled'
OrderStatus::DELIVERED;  // 'delivered'
OrderStatus::CANCELLED;  // 'cancelled'
```

---

## Error Handling

All API errors throw typed exceptions:

```php
use Ucubix\PhpClient\Exceptions\ApiException;
use Ucubix\PhpClient\Exceptions\AuthenticationException;
use Ucubix\PhpClient\Exceptions\RateLimitException;
use Ucubix\PhpClient\Exceptions\ValidationException;

try {
    $order = $client->createOrder($uuid, 5, 'InvalidRegion');
} catch (AuthenticationException $e) {
    // 401 Unauthorized or 403 Forbidden
    // Invalid API key or IP not whitelisted
    echo $e->getMessage();
    echo $e->getCode(); // 401 or 403

} catch (ValidationException $e) {
    // 422 Unprocessable Entity
    echo $e->getMessage(); // e.g. "Quantity must not be greater than 1."
    echo $e->field;        // field name if provided

} catch (RateLimitException $e) {
    // 429 Too Many Requests (after all retries exhausted)
    echo $e->retryAfter;   // seconds to wait

} catch (ApiException $e) {
    // All other errors (400, 404, 500, etc.)
    echo $e->getMessage();
    echo $e->getCode();
    echo $e->errorKey;
    echo $e->errorDetail;
}
```

### Exception Hierarchy

```
ApiException
  ├── AuthenticationException  (401, 403)
  ├── RateLimitException       (429)
  └── ValidationException      (422)
```

---

## Rate Limiting

The client has a dual-layer rate limiting system, matching the [SharpAPI php-core](https://github.com/sharpapi/php-core) pattern.

### 1. Client-side Sliding Window

Proactive throttling: the client tracks request timestamps in a sliding window and blocks (via `usleep` + 50ms buffer) before exceeding the configured requests per minute. This prevents hitting the server limit.

```php
// Check/configure requests per minute (default: 100)
$client->getRequestsPerMinute();   // 100
$client->setRequestsPerMinute(50); // slow down
$client->setRequestsPerMinute(0);  // disable client-side throttling

// Direct access to the rate limiter
$limiter = $client->getRateLimiter();
$limiter->canProceed();  // non-blocking check
$limiter->remaining();   // slots left in current window
```

### 2. Server-side 429 Retry

Reactive handling: if the server returns `429 Too Many Requests`, the client automatically retries up to 3 times, respecting the `Retry-After` header.

```php
// Configure max retries (default: 3)
$client->setMaxRetryOnRateLimit(5);
```

### 3. Server Header Tracking

After every response, the client reads `X-RateLimit-Limit` and `X-RateLimit-Remaining` headers.

```php
$client->getRateLimitLimit();     // e.g. 100
$client->getRateLimitRemaining(); // e.g. 87
$client->canMakeRequest();        // true if remaining > 0
```

### 4. Server-side Limit Adaptation

If the server reports a higher limit via `X-RateLimit-Limit` header, the client automatically adapts its sliding window upward (one-way ratchet — never decreases).

### 5. State Persistence

Export/restore rate limit state for caching across requests:

```php
// Export
$state = $client->getRateLimitState();
// ['limit' => 100, 'remaining' => 87]

// Restore (e.g. from Redis/session)
$client->setRateLimitState($state);
```

### API Rate Limits

- **100 requests per minute** per API key

---

## Authentication

The API uses Bearer Token authentication with IP whitelisting:

```
Authorization: Bearer YOUR_API_KEY
Accept: application/vnd.api+json
Content-Type: application/json
```

Requests from non-whitelisted IPs receive a `403 Forbidden` error.

---

## Testing

```bash
composer install
vendor/bin/phpunit
```

## License

MIT. See [LICENSE](LICENSE).
