<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Ucubix\PhpClient\Dto\OrderItem;

class OrderItemTest extends TestCase
{
    public function test_without_license_key(): void
    {
        $item = new OrderItem(
            id: '1c7718c1-640f-4649-86f0-d8dc6c376f9d',
            price: 338.46,
            country_code: 'SG',
            license_key_uuid: null,
            fulfilled_at: null,
            created_at: '2026-04-07T23:57:32.000000Z',
            updated_at: '2026-04-07T23:57:32.000000Z',
        );

        $this->assertEquals('1c7718c1-640f-4649-86f0-d8dc6c376f9d', $item->id);
        $this->assertEquals(338.46, $item->price);
        $this->assertEquals('SG', $item->country_code);
        $this->assertFalse($item->hasLicenseKey());
    }

    public function test_with_license_key(): void
    {
        $item = new OrderItem(
            id: 'item-uuid',
            price: 19.99,
            country_code: 'US',
            license_key_uuid: 'lk-uuid-456',
            fulfilled_at: '2026-04-07T12:00:00.000000Z',
            created_at: '2026-04-07T10:00:00.000000Z',
            updated_at: '2026-04-07T12:00:00.000000Z',
        );

        $this->assertTrue($item->hasLicenseKey());
        $this->assertEquals('lk-uuid-456', $item->license_key_uuid);
    }
}
