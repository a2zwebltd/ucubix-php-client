<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Ucubix\PhpClient\Dto\Category;
use Ucubix\PhpClient\Dto\Developer;
use Ucubix\PhpClient\Dto\Franchise;
use Ucubix\PhpClient\Dto\LicenseKey;
use Ucubix\PhpClient\Dto\Order;
use Ucubix\PhpClient\Dto\OrderItem;
use Ucubix\PhpClient\Dto\Media;
use Ucubix\PhpClient\Dto\Organisation;
use Ucubix\PhpClient\Dto\Platform;
use Ucubix\PhpClient\Dto\Product;
use Ucubix\PhpClient\Dto\Publisher;
use Ucubix\PhpClient\Exceptions\ApiException;
use Ucubix\PhpClient\Exceptions\AuthenticationException;
use Ucubix\PhpClient\Exceptions\RateLimitException;
use Ucubix\PhpClient\Exceptions\ValidationException;

class ClientTest extends TestCase
{
    private function createClient(array $responses): TestableUcubixClient
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $handler]);

        return new TestableUcubixClient($guzzle);
    }

    private function jsonResponse(array $body, int $status = 200, array $headers = []): Response
    {
        return new Response(
            $status,
            array_merge(['Content-Type' => 'application/vnd.api+json'], $headers),
            json_encode($body),
        );
    }

    private function paginatedMeta(int $currentPage = 1, int $perPage = 15, int $total = 1, int $lastPage = 1): array
    {
        return [
            'meta' => ['page' => compact('currentPage', 'perPage', 'total', 'lastPage')],
            'links' => [
                'first' => 'http://localhost/api/v1/test?page[number]=1',
                'last' => "http://localhost/api/v1/test?page[number]={$lastPage}",
                'next' => $currentPage < $lastPage ? 'http://localhost/api/v1/test?page[number]=' . ($currentPage + 1) : null,
                'prev' => $currentPage > 1 ? 'http://localhost/api/v1/test?page[number]=' . ($currentPage - 1) : null,
            ],
        ];
    }

    // =========================================================================
    // Organisation
    // =========================================================================

    public function test_get_organisation(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => [
                    'organisation' => ['uuid' => 'org-uuid', 'name' => 'My Organisation'],
                    'summary' => ['currencies' => 2, 'total_usd_equivalent' => '1000.00'],
                    'credit_lines' => [['currency' => 'USD', 'balance' => '500.00']],
                ],

            ]),
        ]);

        $org = $client->getOrganisation();

        $this->assertInstanceOf(Organisation::class, $org);
        $this->assertEquals('org-uuid', $org->uuid);
        $this->assertEquals('My Organisation', $org->name);
        $this->assertEquals(2, $org->summary->currencies);
        $this->assertEquals('1000.00', $org->summary->total_usd_equivalent);
        $this->assertCount(1, $org->credit_lines);
    }

    // =========================================================================
    // Products
    // =========================================================================

    public function test_get_products(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [
                    ['id' => 'p1', 'type' => 'products', 'attributes' => ['name' => 'Game 1', 'summary' => null, 'description' => null, 'release_date' => null, 'type' => 'Game', 'created_at' => '2024-01-01T00:00:00.000000Z', 'regional_pricing' => [], 'metadata' => null]],
                    ['id' => 'p2', 'type' => 'products', 'attributes' => ['name' => 'Game 2', 'summary' => null, 'description' => null, 'release_date' => null, 'type' => 'Game', 'created_at' => '2024-01-01T00:00:00.000000Z', 'regional_pricing' => [], 'metadata' => null]],
                ],
            ], $this->paginatedMeta(total: 2))),
        ]);

        $result = $client->getProducts();

        $this->assertCount(2, $result->data);
        $this->assertInstanceOf(Product::class, $result->data[0]);
        $this->assertEquals('Game 1', $result->data[0]->name);
        $this->assertEquals(2, $result->total);
    }

    public function test_get_products_with_filters(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [
                    ['id' => 'p1', 'type' => 'products', 'attributes' => ['name' => 'Filtered Game', 'summary' => null, 'description' => null, 'release_date' => null, 'type' => 'Game', 'created_at' => '2024-01-01T00:00:00.000000Z', 'regional_pricing' => [], 'metadata' => null]],
                ],
            ], $this->paginatedMeta(perPage: 10))),
        ]);

        $result = $client->getProducts(['search' => 'Game', 'category' => 'cat-uuid'], page: 1, perPage: 10, sort: 'name');

        $this->assertCount(1, $result->data);
    }

    public function test_get_products_invalid_filter(): void
    {
        $client = $this->createClient([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid filter(s): foo');
        $client->getProducts(['foo' => 'bar']);
    }

    public function test_get_orders_invalid_filter(): void
    {
        $client = $this->createClient([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid filter(s): status');
        $client->getOrders(['status' => 'new']);
    }

    public function test_get_product(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => [
                    'id' => 'prod-uuid',
                    'type' => 'products',
                    'attributes' => [
                        'name' => 'Test Game',
                        'summary' => 'A short summary',
                        'description' => 'A great game',
                        'release_date' => '2024-03-15T00:00:00.000000Z',
                        'type' => 'Game',
                        'created_at' => '2024-01-01T00:00:00.000000Z',
                        'regional_pricing' => [
                            ['region_code' => 'NA', 'reseller_wsp' => 75, 'countries' => [
                                ['country_name' => 'US', 'country_code' => 'us', 'price' => 49.99, 'currency_code' => 'USD', 'is_promotion' => false, 'original_price' => 49.99, 'promotion_name' => null, 'promotion_end_date' => null, 'can_be_ordered' => true, 'in_stock' => true],
                            ]],
                        ],
                        'metadata' => null,
                    ],
                ],
            ]),
        ]);

        $product = $client->getProduct('prod-uuid');

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('prod-uuid', $product->id);
        $this->assertCount(1, $product->regional_pricing);
    }

    public function test_get_product_photos(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'ph1', 'attributes' => ['name' => 'photo1.jpg', 'file_name' => 'photo1.jpg', 'collection_name' => 'photos', 'mime_type' => 'image/jpeg', 'disk' => 's3', 'size' => 204800, 'order_column' => 1, 'url' => 'https://img.com/1.jpg', 'created_at' => '2024-01-01T00:00:00.000000Z', 'updated_at' => '2024-01-01T00:00:00.000000Z']]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getProductPhotos('prod-uuid');
        $this->assertCount(1, $result->data);
        $this->assertInstanceOf(Media::class, $result->data[0]);
    }

    public function test_get_product_screenshots(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'ss1', 'attributes' => ['name' => 'screenshot1.jpg', 'file_name' => 'screenshot1.jpg', 'collection_name' => 'screenshots', 'mime_type' => 'image/jpeg', 'disk' => 's3', 'size' => 512000, 'order_column' => 1, 'url' => 'https://img.com/ss1.jpg', 'created_at' => '2024-01-01T00:00:00.000000Z', 'updated_at' => '2024-01-01T00:00:00.000000Z']]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getProductScreenshots('prod-uuid');
        $this->assertCount(1, $result->data);
        $this->assertInstanceOf(Media::class, $result->data[0]);
    }

    public function test_get_product_categories(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'cat1', 'attributes' => ['name' => 'Action', 'parent_id' => null, 'child_ids' => []]]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getProductCategories('prod-uuid');
        $this->assertCount(1, $result->data);
        $this->assertInstanceOf(Category::class, $result->data[0]);
    }

    public function test_get_product_publishers(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'pub1', 'attributes' => ['name' => 'Big Publisher', 'website' => 'https://bigpublisher.com', 'about' => 'A major publisher', 'created_at' => '2024-01-01T00:00:00.000000Z', 'updated_at' => '2024-06-01T00:00:00.000000Z']]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getProductPublishers('prod-uuid');
        $this->assertCount(1, $result->data);
        $this->assertInstanceOf(Publisher::class, $result->data[0]);
    }

    public function test_get_product_platforms(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'plat1', 'attributes' => ['name' => 'Steam', 'created_at' => '2024-01-01T00:00:00.000000Z', 'updated_at' => '2024-06-01T00:00:00.000000Z']]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getProductPlatforms('prod-uuid');
        $this->assertCount(1, $result->data);
        $this->assertInstanceOf(Platform::class, $result->data[0]);
    }

    public function test_get_product_franchises(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'f1', 'attributes' => ['name' => 'Test Franchise', 'created_at' => '2024-01-01T00:00:00.000000Z']]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getProductFranchises('prod-uuid');
        $this->assertCount(1, $result->data);
        $this->assertInstanceOf(Franchise::class, $result->data[0]);
    }

    public function test_get_product_developers(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'd1', 'attributes' => ['name' => 'Dev Studio', 'website' => 'https://devstudio.com', 'about' => 'An indie studio', 'created_at' => '2024-01-01T00:00:00.000000Z', 'updated_at' => '2024-06-01T00:00:00.000000Z']]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getProductDevelopers('prod-uuid');
        $this->assertCount(1, $result->data);
        $this->assertInstanceOf(Developer::class, $result->data[0]);
    }

    // =========================================================================
    // Orders
    // =========================================================================

    public function test_get_orders(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [
                    ['id' => 'o1', 'type' => 'orders', 'attributes' => [
                        'code' => 'ORD-001', 'external_reference' => null, 'external_reference_attempt' => null,
                        'status' => 'pending', 'total_price' => 100.00, 'srp' => 100.00, 'estimated_cost' => 80.00,
                        'items_count' => 1, 'currency_code' => 'USD', 'order_date' => '2026-04-06T12:00:00.000000Z',
                        'approved_at' => null, 'rejected_at' => null, 'delivered_at' => null,
                        'distribution_model' => 'sale', 'rejection_note' => null,
                    ]],
                ],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getOrders();
        $this->assertCount(1, $result->data);
        $this->assertInstanceOf(Order::class, $result->data[0]);
        $this->assertEquals('ORD-001', $result->data[0]->code);
    }

    public function test_get_order(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => [
                    'id' => 'order-uuid',
                    'type' => 'orders',
                    'attributes' => [
                        'code' => 'ORD-002',
                        'external_reference' => null,
                        'external_reference_attempt' => null,
                        'status' => 'fulfilled',
                        'total_price' => 199.90,
                        'srp' => 199.90,
                        'estimated_cost' => 150.00,
                        'items_count' => 2,
                        'currency_code' => 'USD',
                        'order_date' => '2026-04-05T10:00:00.000000Z',
                        'approved_at' => '2026-04-05T14:00:00.000000Z',
                        'rejected_at' => null,
                        'delivered_at' => null,
                        'distribution_model' => 'sale',
                        'rejection_note' => null,
                    ],
                ],
            ]),
        ]);

        $order = $client->getOrder('order-uuid');
        $this->assertEquals('order-uuid', $order->id);
        $this->assertEquals('fulfilled', $order->status);
        $this->assertEquals(199.90, $order->srp);
    }

    public function test_get_order_items(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [
                    ['id' => 'oi1', 'attributes' => [
                        'price' => 100.00, 'country_code' => 'SG', 'license_key_uuid' => 'lk1', 'fulfilled_at' => '2026-04-07T12:00:00Z',
                        'created_at' => '2026-04-07T10:00:00.000000Z', 'updated_at' => '2026-04-07T12:00:00.000000Z',
                    ]],
                    ['id' => 'oi2', 'attributes' => [
                        'price' => 99.90, 'country_code' => 'SG', 'license_key_uuid' => null, 'fulfilled_at' => null,
                        'created_at' => '2026-04-07T10:00:00.000000Z', 'updated_at' => '2026-04-07T10:00:00.000000Z',
                    ]],
                ],
            ], $this->paginatedMeta(total: 2))),
        ]);

        $result = $client->getOrderItems('order-uuid');
        $this->assertCount(2, $result->data);
        $this->assertTrue($result->data[0]->hasLicenseKey());
        $this->assertFalse($result->data[1]->hasLicenseKey());
        $this->assertEquals('SG', $result->data[0]->country_code);
    }

    public function test_create_order(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => [
                    'order' => [
                        'uuid' => 'new-order-uuid',
                        'quantity' => 3,
                        'total_price' => 59.97,
                        'currency_code' => 'USD',
                        'date' => '2026-04-08T10:00:00.000000Z',
                        'status' => 'new',
                        'external_reference' => null,
                        'srp' => 59.97,
                        'estimated_cost' => 45.00,
                    ],
                ],
                'message' => 'Order created successfully',
            ], 201),
        ]);

        $order = $client->createOrder('prod-uuid', 3, 'NA');
        $this->assertEquals('new-order-uuid', $order->id);
        $this->assertEquals('new', $order->status);
    }

    public function test_update_order(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => [
                    'order' => [
                        'uuid' => 'order-uuid',
                        'quantity' => 100,
                        'total_price' => 500.00,
                        'currency_code' => 'USD',
                        'date' => '2026-04-07T10:00:00.000000Z',
                        'status' => 'new',
                        'external_reference' => null,
                        'srp' => 500.00,
                        'estimated_cost' => 400.00,
                    ],
                ],
            ]),
        ]);

        $order = $client->updateOrder('order-uuid', 100);
        $this->assertEquals('new', $order->status);
    }

    public function test_cancel_order(): void
    {
        $client = $this->createClient([
            new Response(200, [], ''),
        ]);

        $result = $client->cancelOrder('order-uuid');
        $this->assertTrue($result);
    }

    // =========================================================================
    // License Keys
    // =========================================================================

    public function test_get_license_key(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => [
                    'id' => 'lk-uuid',
                    'type' => 'license-keys',
                    'attributes' => [
                        'license_key' => 'ABCD-EFGH-IJKL-MNOP',
                        'created_at' => '2024-01-15T10:30:00.000000Z',
                        'updated_at' => '2024-01-15T10:30:00.000000Z',
                    ],
                ],
            ]),
        ]);

        $key = $client->getLicenseKey('lk-uuid');
        $this->assertInstanceOf(LicenseKey::class, $key);
        $this->assertEquals('ABCD-EFGH-IJKL-MNOP', $key->license_key);
    }

    public function test_get_bulk_license_keys(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => [
                    ['id' => 'lk1', 'attributes' => ['license_key' => 'KEY-1', 'created_at' => '2024-01-15T10:30:00.000000Z', 'updated_at' => '2024-01-15T10:30:00.000000Z']],
                    ['id' => 'lk2', 'attributes' => ['license_key' => 'KEY-2', 'created_at' => '2024-01-15T10:30:00.000000Z', 'updated_at' => '2024-01-15T10:30:00.000000Z']],
                    ['id' => 'lk3', 'attributes' => ['license_key' => 'KEY-3', 'created_at' => '2024-01-15T10:30:00.000000Z', 'updated_at' => '2024-01-15T10:30:00.000000Z']],
                ],
            ]),
        ]);

        $keys = $client->getBulkLicenseKeys(['lk1', 'lk2', 'lk3']);
        $this->assertCount(3, $keys);
        $this->assertEquals('KEY-1', $keys[0]->license_key);
        $this->assertEquals('KEY-3', $keys[2]->license_key);
    }

    // =========================================================================
    // Catalog Dictionaries
    // =========================================================================

    public function test_get_categories(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [
                    ['id' => 'c1', 'attributes' => ['name' => 'Action', 'parent_id' => null, 'child_ids' => []]],
                    ['id' => 'c2', 'attributes' => ['name' => 'RPG', 'parent_id' => null, 'child_ids' => []]],
                ],
            ], $this->paginatedMeta(total: 2))),
        ]);

        $result = $client->getCategories();
        $this->assertCount(2, $result->data);
        $this->assertInstanceOf(Category::class, $result->data[0]);
    }

    public function test_get_publishers(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'p1', 'attributes' => ['name' => 'Publisher 1', 'website' => null, 'about' => null, 'created_at' => '2024-01-01T00:00:00.000000Z', 'updated_at' => '2024-06-01T00:00:00.000000Z']]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getPublishers();
        $this->assertInstanceOf(Publisher::class, $result->data[0]);
    }

    public function test_get_platforms(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'pl1', 'attributes' => ['name' => 'Steam', 'created_at' => '2024-01-01T00:00:00.000000Z', 'updated_at' => '2024-06-01T00:00:00.000000Z']]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getPlatforms();
        $this->assertInstanceOf(Platform::class, $result->data[0]);
    }

    public function test_get_developers(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'd1', 'attributes' => ['name' => 'Dev Studio', 'website' => null, 'about' => null, 'created_at' => '2024-01-01T00:00:00.000000Z', 'updated_at' => '2024-06-01T00:00:00.000000Z']]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getDevelopers();
        $this->assertInstanceOf(Developer::class, $result->data[0]);
    }

    public function test_get_franchises(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(array_merge([
                'data' => [['id' => 'f1', 'attributes' => ['name' => 'Test Franchise', 'created_at' => '2024-01-01T00:00:00.000000Z']]],
            ], $this->paginatedMeta())),
        ]);

        $result = $client->getFranchises();
        $this->assertInstanceOf(Franchise::class, $result->data[0]);
    }

    // =========================================================================
    // Rate Limiting & Error Handling
    // =========================================================================

    public function test_rate_limit_headers_extracted(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(
                ['data' => ['organisation' => ['uuid' => 'x', 'name' => 'Test'], 'summary' => ['currencies' => 1, 'total_usd_equivalent' => '500.00'], 'credit_lines' => [['currency' => 'USD', 'balance' => '500.00']]]],
                200,
                ['X-RateLimit-Limit' => '100', 'X-RateLimit-Remaining' => '95'],
            ),
        ]);

        $client->getOrganisation();

        $this->assertEquals(100, $client->getRateLimitLimit());
        $this->assertEquals(95, $client->getRateLimitRemaining());
    }

    public function test_429_retry_then_success(): void
    {
        $client = $this->createClient([
            new Response(429, ['Retry-After' => '0'], json_encode(['message' => 'Rate limited'])),
            $this->jsonResponse(['data' => ['organisation' => ['uuid' => 'x', 'name' => 'Test'], 'summary' => ['currencies' => 1, 'total_usd_equivalent' => '500.00'], 'credit_lines' => [['currency' => 'USD', 'balance' => '500.00']]]]),
        ]);

        $org = $client->getOrganisation();
        $this->assertEquals('Test', $org->name);
    }

    public function test_429_retry_exhaustion(): void
    {
        $client = $this->createClient([
            new Response(429, ['Retry-After' => '0'], json_encode(['message' => 'Rate limited'])),
            new Response(429, ['Retry-After' => '0'], json_encode(['message' => 'Rate limited'])),
            new Response(429, ['Retry-After' => '0'], json_encode(['message' => 'Rate limited'])),
            new Response(429, ['Retry-After' => '0'], json_encode(['message' => 'Rate limited'])),
        ]);

        $this->expectException(RateLimitException::class);
        $client->getOrganisation();
    }

    public function test_custom_max_retry(): void
    {
        $client = $this->createClient([
            new Response(429, ['Retry-After' => '0'], json_encode(['message' => 'Rate limited'])),
            new Response(429, ['Retry-After' => '0'], json_encode(['message' => 'Rate limited'])),
        ]);

        $client->setMaxRetryOnRateLimit(1);
        $this->assertEquals(1, $client->getMaxRetryOnRateLimit());

        $this->expectException(RateLimitException::class);
        $client->getOrganisation();
    }

    public function test_401_throws_authentication_exception(): void
    {
        $client = $this->createClient([
            new Response(401, [], json_encode(['error' => ['message' => 'Unauthorized']])),
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);
        $client->getOrganisation();
    }

    public function test_403_throws_authentication_exception(): void
    {
        $client = $this->createClient([
            new Response(403, [], json_encode(['message' => 'Forbidden'])),
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(403);
        $client->getOrganisation();
    }

    public function test_422_throws_validation_exception(): void
    {
        $client = $this->createClient([
            new Response(422, [], json_encode([
                'message' => 'Order creation failed',
                'error' => 'Pricing information not found',
                'key' => 'region',
            ])),
        ]);

        $this->expectException(ValidationException::class);
        $client->createOrder('prod-uuid', 1, 'INVALID');
    }

    public function test_404_throws_api_exception(): void
    {
        $client = $this->createClient([
            new Response(404, [], json_encode(['message' => 'Not Found'])),
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(404);
        $client->getOrder('non-existent-uuid');
    }

    public function test_500_throws_api_exception(): void
    {
        $client = $this->createClient([
            new Response(500, [], json_encode(['message' => 'Internal Server Error'])),
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(500);
        $client->getOrganisation();
    }
}
