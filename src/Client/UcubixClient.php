<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Ucubix\PhpClient\Dto\Category;
use Ucubix\PhpClient\Dto\CreditLine;
use Ucubix\PhpClient\Dto\Developer;
use Ucubix\PhpClient\Dto\Franchise;
use Ucubix\PhpClient\Dto\LicenseKey;
use Ucubix\PhpClient\Dto\Media;
use Ucubix\PhpClient\Dto\Order;
use Ucubix\PhpClient\Dto\OrderItem;
use Ucubix\PhpClient\Dto\Organisation;
use Ucubix\PhpClient\Dto\OrganisationSummary;
use Ucubix\PhpClient\Dto\PaginatedResponse;
use Ucubix\PhpClient\Dto\Platform;
use Ucubix\PhpClient\Dto\Product;
use Ucubix\PhpClient\Dto\ProductMetadata;
use Ucubix\PhpClient\Dto\Publisher;
use Ucubix\PhpClient\Dto\CountryPrice;
use Ucubix\PhpClient\Dto\RegionalPricing;
use Ucubix\PhpClient\Dto\SteamdbInfo;
use Ucubix\PhpClient\Dto\SystemRequirement;
use Ucubix\PhpClient\Exceptions\ApiException;
use Ucubix\PhpClient\Exceptions\AuthenticationException;
use Ucubix\PhpClient\Exceptions\RateLimitException;
use Ucubix\PhpClient\Exceptions\ValidationException;
use Ucubix\PhpClient\RateLimit\SlidingWindowRateLimiter;

class UcubixClient
{
    public const VERSION = '1.0.0';
    public const DEFAULT_BASE_URL = 'https://ucubix.com/api/v1/';

    private GuzzleClient $httpClient;
    private SlidingWindowRateLimiter $rateLimiter;

    private ?int $rateLimitRemaining = null;
    private ?int $rateLimitLimit = null;

    private int $maxRetryOnRateLimit = 3;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
    ) {
        $this->rateLimiter = new SlidingWindowRateLimiter(maxRequests: 100, windowSeconds: 60);

        $this->httpClient = new GuzzleClient([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Authorization' => 'Bearer ' . $this->apiKey,
                'User-Agent' => 'UcubixPHPClient/' . self::VERSION,
            ],
        ]);
    }

    // =========================================================================
    // General Info
    // =========================================================================

    public function getOrganisation(): Organisation
    {
        $response = $this->get('info');
        $data = $response['data'];

        return new Organisation(
            uuid: $data['organisation']['uuid'],
            name: $data['organisation']['name'],
            summary: new OrganisationSummary(
                currencies: (int) $data['summary']['currencies'],
                total_usd_equivalent: $data['summary']['total_usd_equivalent'],
            ),
            credit_lines: array_map(
                fn(array $cl) => new CreditLine(currency: $cl['currency'], balance: $cl['balance']),
                $data['credit_lines'],
            ),
        );
    }

    // =========================================================================
    // Products Catalog
    // =========================================================================

    private const PRODUCT_FILTERS = ['search', 'category', 'publisher', 'developer', 'franchise', 'platform'];

    /**
     * @param array $filters Allowed keys: search, category, publisher, developer, franchise, platform
     * @return PaginatedResponse<Product>
     */
    public function getProducts(array $filters = [], int $page = 1, int $perPage = 15, ?string $sort = null): PaginatedResponse
    {
        $this->validateFilters($filters, self::PRODUCT_FILTERS);

        $query = $this->buildPaginationQuery($page, $perPage, $sort);
        $this->applyFilters($query, $filters);

        $response = $this->get('products', $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseProduct($item));
    }

    public function getProduct(string $id): Product
    {
        $response = $this->get("products/{$id}");

        return $this->parseProduct($response['data']);
    }

    /**
     * @return PaginatedResponse<Media>
     */
    public function getProductPhotos(string $id, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage);
        $response = $this->get("products/{$id}/photos", $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseMedia($item));
    }

    /**
     * @return PaginatedResponse<Media>
     */
    public function getProductScreenshots(string $id, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage);
        $response = $this->get("products/{$id}/screenshots", $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseMedia($item));
    }

    /**
     * @return PaginatedResponse<Category>
     */
    public function getProductCategories(string $id, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage);
        $response = $this->get("products/{$id}/categories", $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseCategory($item));
    }

    /**
     * @return PaginatedResponse<Publisher>
     */
    public function getProductPublishers(string $id, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage);
        $response = $this->get("products/{$id}/publishers", $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parsePublisher($item));
    }

    /**
     * @return PaginatedResponse<Platform>
     */
    public function getProductPlatforms(string $id, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage);
        $response = $this->get("products/{$id}/platforms", $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parsePlatform($item));
    }

    /**
     * @return PaginatedResponse<Franchise>
     */
    public function getProductFranchises(string $id, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage);
        $response = $this->get("products/{$id}/franchises", $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseFranchise($item));
    }

    /**
     * @return PaginatedResponse<Developer>
     */
    public function getProductDevelopers(string $id, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage);
        $response = $this->get("products/{$id}/developers", $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseDeveloper($item));
    }

    // =========================================================================
    // Orders
    // =========================================================================

    private const ORDER_FILTERS = ['code', 'external_reference'];

    /**
     * @param array $filters Allowed keys: code, external_reference
     * @return PaginatedResponse<Order>
     */
    public function getOrders(array $filters = [], int $page = 1, int $perPage = 15, string $sort = '-order_date'): PaginatedResponse
    {
        $this->validateFilters($filters, self::ORDER_FILTERS);

        $query = $this->buildPaginationQuery($page, $perPage, $sort);
        $this->applyFilters($query, $filters);

        $response = $this->get('orders', $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseOrder($item));
    }

    public function getOrder(string $id): Order
    {
        $response = $this->get("orders/{$id}");

        return $this->parseOrder($response['data']);
    }

    /**
     * @return PaginatedResponse<OrderItem>
     */
    public function getOrderItems(string $orderId, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage);
        $response = $this->get("orders/{$orderId}/order-items", $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseOrderItem($item));
    }

    public function createOrder(string $productUuid, int $quantity, string $regionCode, ?string $countryCode = null): Order
    {
        $body = [
            'product_uuid' => $productUuid,
            'quantity' => $quantity,
            'region_code' => $regionCode,
        ];

        if ($countryCode !== null) {
            $body['country_code'] = $countryCode;
        }

        $response = $this->post('orders', $body);

        return $this->parseCustomOrderResponse($response['data']['order']);
    }

    public function updateOrder(string $id, int $quantity): Order
    {
        $response = $this->patch("orders/{$id}", ['quantity' => $quantity]);

        return $this->parseCustomOrderResponse($response['data']['order']);
    }

    public function cancelOrder(string $id): bool
    {
        $this->delete("orders/{$id}/cancel");

        return true;
    }

    // =========================================================================
    // License Keys
    // =========================================================================

    public function getLicenseKey(string $id): LicenseKey
    {
        $response = $this->get("license-key/{$id}");

        return $this->parseLicenseKey($response['data']);
    }

    /**
     * @param string[] $ids Up to 1000 IDs
     * @return LicenseKey[]
     */
    public function getBulkLicenseKeys(array $ids): array
    {
        $response = $this->post('license-key', $ids);

        return array_map(fn(array $item) => $this->parseLicenseKey($item), $response['data']);
    }

    // =========================================================================
    // Catalog Dictionaries
    // =========================================================================

    /**
     * @return PaginatedResponse<Category>
     */
    public function getCategories(int $page = 1, int $perPage = 15, ?string $sort = null): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage, $sort);
        $response = $this->get('product-categories', $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseCategory($item));
    }

    /**
     * @return PaginatedResponse<Publisher>
     */
    public function getPublishers(int $page = 1, int $perPage = 15, ?string $sort = null): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage, $sort);
        $response = $this->get('publishers', $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parsePublisher($item));
    }

    /**
     * @return PaginatedResponse<Platform>
     */
    public function getPlatforms(int $page = 1, int $perPage = 15, ?string $sort = null): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage, $sort);
        $response = $this->get('product-platforms', $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parsePlatform($item));
    }

    /**
     * @return PaginatedResponse<Developer>
     */
    public function getDevelopers(int $page = 1, int $perPage = 15, ?string $sort = null): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage, $sort);
        $response = $this->get('developers', $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseDeveloper($item));
    }

    /**
     * @return PaginatedResponse<Franchise>
     */
    public function getFranchises(int $page = 1, int $perPage = 15, ?string $sort = null): PaginatedResponse
    {
        $query = $this->buildPaginationQuery($page, $perPage, $sort);
        $response = $this->get('franchises', $query);

        return $this->parsePaginatedResponse($response, fn(array $item) => $this->parseFranchise($item));
    }

    // =========================================================================
    // Configuration
    // =========================================================================

    public function setMaxRetryOnRateLimit(int $max): self
    {
        $this->maxRetryOnRateLimit = $max;
        return $this;
    }

    public function getMaxRetryOnRateLimit(): int
    {
        return $this->maxRetryOnRateLimit;
    }

    public function getRateLimitRemaining(): ?int
    {
        return $this->rateLimitRemaining;
    }

    public function getRateLimitLimit(): ?int
    {
        return $this->rateLimitLimit;
    }

    /**
     * Check if server-reported remaining > 0.
     */
    public function canMakeRequest(): bool
    {
        if ($this->rateLimitRemaining === null) {
            return true;
        }

        return $this->rateLimitRemaining > 0;
    }

    /**
     * Get requests per minute setting.
     */
    public function getRequestsPerMinute(): int
    {
        return $this->rateLimiter->getMaxRequests();
    }

    /**
     * Set requests per minute. Pass 0 to disable client-side throttling.
     */
    public function setRequestsPerMinute(int $rpm): self
    {
        if ($rpm <= 0) {
            $this->rateLimiter->setMaxRequests(PHP_INT_MAX);
        } else {
            $this->rateLimiter->setMaxRequests($rpm);
        }

        return $this;
    }

    /**
     * Get direct access to the rate limiter.
     */
    public function getRateLimiter(): SlidingWindowRateLimiter
    {
        return $this->rateLimiter;
    }

    /**
     * Export rate limit state for external caching.
     */
    public function getRateLimitState(): array
    {
        return [
            'limit' => $this->rateLimitLimit,
            'remaining' => $this->rateLimitRemaining,
        ];
    }

    /**
     * Restore rate limit state from external cache.
     */
    public function setRateLimitState(array $state): self
    {
        $this->rateLimitLimit = $state['limit'] ?? null;
        $this->rateLimitRemaining = $state['remaining'] ?? null;

        return $this;
    }

    // =========================================================================
    // Response parsers
    // =========================================================================

    private function parseProduct(array $data): Product
    {
        $a = $data['attributes'];

        $pricing = [];
        if (isset($a['regional_pricing'])) {
            foreach ($a['regional_pricing'] as $region) {
                $pricing[] = new RegionalPricing(
                    region_code: $region['region_code'],
                    reseller_wsp: (float) $region['reseller_wsp'],
                    countries: array_map(fn(array $c) => new CountryPrice(
                        country_name: $c['country_name'],
                        country_code: $c['country_code'],
                        price: $c['price'] !== null ? (float) $c['price'] : null,
                        currency_code: $c['currency_code'],
                        is_promotion: $c['is_promotion'],
                        original_price: $c['original_price'] !== null ? (float) $c['original_price'] : null,
                        promotion_name: $c['promotion_name'],
                        promotion_end_date: $c['promotion_end_date'],
                        can_be_ordered: $c['can_be_ordered'],
                        in_stock: $c['in_stock'],
                    ), $region['countries']),
                );
            }
        }

        $metadata = null;
        if (isset($a['metadata']) && $a['metadata'] !== null) {
            $sysReq = $a['metadata']['system_requirements'] ?? null;
            $steamdb = $a['metadata']['steamdb'] ?? null;

            $metadata = new ProductMetadata(
                minimum: $sysReq !== null ? array_map(
                    fn(array $item) => new SystemRequirement(parameter: $item['parameter'], value: $item['value']),
                    $sysReq['minimum'] ?? [],
                ) : [],
                recommended: $sysReq !== null ? array_map(
                    fn(array $item) => new SystemRequirement(parameter: $item['parameter'], value: $item['value']),
                    $sysReq['recommended'] ?? [],
                ) : [],
                steamdb: $steamdb !== null ? new SteamdbInfo(
                    id: $steamdb['id'],
                    type: $steamdb['type'],
                    url: $steamdb['url'],
                ) : null,
            );
        }

        return new Product(
            id: $data['id'],
            name: $a['name'],
            summary: $a['summary'],
            description: $a['description'],
            release_date: $a['release_date'],
            type: $a['type'],
            created_at: $a['created_at'],
            regional_pricing: $pricing,
            metadata: $metadata,
        );
    }

    private function parseOrder(array $data): Order
    {
        $a = $data['attributes'];

        return new Order(
            id: $data['id'],
            code: $a['code'],
            external_reference: $a['external_reference'],
            external_reference_attempt: $a['external_reference_attempt'],
            status: $a['status'],
            total_price: (float) $a['total_price'],
            srp: (float) $a['srp'],
            estimated_cost: $a['estimated_cost'] !== null ? (float) $a['estimated_cost'] : null,
            items_count: (int) $a['items_count'],
            currency_code: $a['currency_code'],
            order_date: $a['order_date'],
            approved_at: $a['approved_at'],
            rejected_at: $a['rejected_at'],
            delivered_at: $a['delivered_at'],
            distribution_model: $a['distribution_model'],
            rejection_note: $a['rejection_note'],
        );
    }

    private function parseCustomOrderResponse(array $o): Order
    {
        return new Order(
            id: $o['uuid'],
            code: $o['code'] ?? '',
            external_reference: $o['external_reference'],
            external_reference_attempt: $o['external_reference_attempt'] ?? null,
            status: $o['status'],
            total_price: (float) $o['total_price'],
            srp: (float) $o['srp'],
            estimated_cost: isset($o['estimated_cost']) ? (float) $o['estimated_cost'] : null,
            items_count: (int) ($o['items_count'] ?? $o['quantity'] ?? 0),
            currency_code: $o['currency_code'],
            order_date: $o['date'] ?? $o['order_date'] ?? '',
            approved_at: $o['approved_at'] ?? null,
            rejected_at: $o['rejected_at'] ?? null,
            delivered_at: $o['delivered_at'] ?? null,
            distribution_model: $o['distribution_model'] ?? null,
            rejection_note: $o['rejection_note'] ?? null,
        );
    }

    private function parseOrderItem(array $data): OrderItem
    {
        $a = $data['attributes'];

        return new OrderItem(
            id: $data['id'],
            price: (float) $a['price'],
            country_code: $a['country_code'],
            license_key_uuid: $a['license_key_uuid'],
            fulfilled_at: $a['fulfilled_at'],
            created_at: $a['created_at'],
            updated_at: $a['updated_at'],
        );
    }

    private function parseLicenseKey(array $data): LicenseKey
    {
        $a = $data['attributes'];

        return new LicenseKey(
            id: $data['id'],
            license_key: $a['license_key'],
            created_at: $a['created_at'],
            updated_at: $a['updated_at'],
        );
    }

    private function parseMedia(array $data): Media
    {
        $a = $data['attributes'];

        return new Media(
            id: $data['id'],
            name: $a['name'],
            file_name: $a['file_name'],
            collection_name: $a['collection_name'],
            mime_type: $a['mime_type'],
            disk: $a['disk'],
            size: (int) $a['size'],
            order_column: $a['order_column'] !== null ? (int) $a['order_column'] : null,
            url: $a['url'],
            created_at: $a['created_at'],
            updated_at: $a['updated_at'],
        );
    }

    private function parseCategory(array $data): Category
    {
        $a = $data['attributes'];

        return new Category(
            id: $data['id'],
            name: $a['name'],
            parent_id: $a['parent_id'],
            child_ids: $a['child_ids'],
        );
    }

    private function parsePublisher(array $data): Publisher
    {
        $a = $data['attributes'];

        return new Publisher(
            id: $data['id'],
            name: $a['name'],
            website: $a['website'],
            about: $a['about'],
            created_at: $a['created_at'],
            updated_at: $a['updated_at'],
        );
    }

    private function parseDeveloper(array $data): Developer
    {
        $a = $data['attributes'];

        return new Developer(
            id: $data['id'],
            name: $a['name'],
            website: $a['website'],
            about: $a['about'],
            created_at: $a['created_at'],
            updated_at: $a['updated_at'],
        );
    }

    private function parsePlatform(array $data): Platform
    {
        $a = $data['attributes'];

        return new Platform(
            id: $data['id'],
            name: $a['name'],
            created_at: $a['created_at'],
            updated_at: $a['updated_at'],
        );
    }

    private function parseFranchise(array $data): Franchise
    {
        $a = $data['attributes'];

        return new Franchise(
            id: $data['id'],
            name: $a['name'],
            created_at: $a['created_at'],
        );
    }


    /**
     * @template T
     * @param callable(array): T $mapper
     * @return PaginatedResponse<T>
     */
    private function parsePaginatedResponse(array $response, callable $mapper): PaginatedResponse
    {
        $meta = $response['meta']['page'];
        $links = $response['links'];

        return new PaginatedResponse(
            data: array_map($mapper, $response['data']),
            currentPage: (int) $meta['currentPage'],
            perPage: (int) $meta['perPage'],
            total: (int) $meta['total'],
            lastPage: (int) $meta['lastPage'],
            firstPageUrl: $links['first'],
            lastPageUrl: $links['last'],
            nextPageUrl: $links['next'] ?? null,
            prevPageUrl: $links['prev'] ?? null,
        );
    }

    // =========================================================================
    // HTTP layer
    // =========================================================================

    protected function get(string $endpoint, array $query = []): array
    {
        return $this->executeWithRateLimitRetry('GET', $endpoint, ['query' => $query]);
    }

    protected function post(string $endpoint, array $body = []): array
    {
        return $this->executeWithRateLimitRetry('POST', $endpoint, ['json' => $body]);
    }

    protected function patch(string $endpoint, array $body = []): array
    {
        return $this->executeWithRateLimitRetry('PATCH', $endpoint, ['json' => $body]);
    }

    protected function delete(string $endpoint): array
    {
        return $this->executeWithRateLimitRetry('DELETE', $endpoint);
    }

    private function executeWithRateLimitRetry(string $method, string $endpoint, array $options = []): array
    {
        $attempts = 0;

        while (true) {
            $this->rateLimiter->waitIfNeeded();

            try {
                $response = $this->httpClient->request($method, $endpoint, $options);
                $this->extractRateLimitHeaders($response);

                $body = $response->getBody()->getContents();
                if (empty($body)) {
                    return [];
                }

                return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (ClientException $e) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $this->extractRateLimitHeaders($response);

                if ($statusCode === 429) {
                    $attempts++;
                    if ($attempts > $this->maxRetryOnRateLimit) {
                        $retryAfter = $this->parseRetryAfter($response);
                        throw new RateLimitException(
                            'Rate limit exceeded after ' . $this->maxRetryOnRateLimit . ' retries',
                            $retryAfter,
                            $e,
                        );
                    }

                    $retryAfter = $this->parseRetryAfter($response) ?? 1;
                    sleep($retryAfter);
                    continue;
                }

                $this->throwApiException($statusCode, $response, $e);
            } catch (GuzzleException $e) {
                throw new ApiException(
                    'HTTP request failed: ' . $e->getMessage(),
                    $e->getCode(),
                    $e,
                );
            }
        }
    }

    private function extractRateLimitHeaders($response): void
    {
        $limit = $response->getHeaderLine('X-RateLimit-Limit');
        $remaining = $response->getHeaderLine('X-RateLimit-Remaining');

        if ($limit !== '') {
            $this->rateLimitLimit = (int) $limit;
            $this->rateLimiter->adaptFromServerLimit($this->rateLimitLimit);
        }

        if ($remaining !== '') {
            $this->rateLimitRemaining = (int) $remaining;
        }
    }

    private function parseRetryAfter($response): ?int
    {
        $header = $response->getHeaderLine('Retry-After');

        return $header !== '' ? (int) $header : null;
    }

    private function throwApiException(int $statusCode, $response, \Throwable $previous): never
    {
        $body = json_decode($response->getBody()->getContents(), true) ?? [];

        // JSON:API error format: {"errors":[{"detail":"...","code":"..."}]}
        if (isset($body['errors']) && is_array($body['errors'])) {
            $firstError = $body['errors'][0] ?? [];
            $message = $firstError['detail'] ?? $firstError['title'] ?? 'API request failed';
            $errorKey = $firstError['source']['pointer'] ?? null;
            $errorDetail = $message;
        }
        // Custom error format: {"error":{"message":"..."}} or {"message":"...","error":"..."}
        else {
            $error = $body['error'] ?? [];
            if (is_array($error)) {
                $message = $error['message'] ?? $body['message'] ?? 'API request failed';
                $errorDetail = $error['detail'] ?? null;
            } else {
                $message = $body['message'] ?? (is_string($error) ? $error : 'API request failed');
                $errorDetail = is_string($error) ? $error : null;
            }
            $errorKey = $body['key'] ?? null;
        }

        match ($statusCode) {
            401 => throw new AuthenticationException($message, $statusCode, $previous),
            403 => throw new AuthenticationException($message, $statusCode, $previous),
            422 => throw new ValidationException($message, $errorKey, $previous),
            default => throw new ApiException($message, $statusCode, $previous, $errorKey, $errorDetail),
        };
    }

    private function validateFilters(array $filters, array $allowed): void
    {
        $invalid = array_diff(array_keys($filters), $allowed);
        if (!empty($invalid)) {
            throw new \InvalidArgumentException(
                'Invalid filter(s): ' . implode(', ', $invalid) . '. Allowed: ' . implode(', ', $allowed)
            );
        }
    }

    private function applyFilters(array &$query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            $query["filter[{$key}]"] = $value;
        }
    }

    private function buildPaginationQuery(int $page = 1, int $perPage = 15, ?string $sort = null): array
    {
        $query = [
            'page[number]' => $page,
            'page[size]' => $perPage,
        ];

        if ($sort !== null) {
            $query['sort'] = $sort;
        }

        return $query;
    }
}
