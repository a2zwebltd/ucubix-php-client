<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Ucubix\PhpClient\Dto\Order;
use Ucubix\PhpClient\Enums\OrderStatus;

class OrderTest extends TestCase
{
    private function makeOrder(string $status = 'delivered'): Order
    {
        return new Order(
            id: '03dacf06-3776-41bf-b937-a8f6219458c5',
            code: 'IC69848708',
            external_reference: null,
            external_reference_attempt: null,
            status: $status,
            total_price: 812.04,
            srp: 812.04,
            estimated_cost: 700.63,
            items_count: 2,
            currency_code: 'SGD',
            order_date: '2026-04-06T12:08:49.000000Z',
            approved_at: '2026-04-06T16:08:49.000000Z',
            rejected_at: null,
            delivered_at: '2026-04-06T19:08:49.000000Z',
            distribution_model: 'sale',
            rejection_note: null,
        );
    }

    public function test_constructor_and_properties(): void
    {
        $order = $this->makeOrder();

        $this->assertEquals('03dacf06-3776-41bf-b937-a8f6219458c5', $order->id);
        $this->assertEquals('IC69848708', $order->code);
        $this->assertEquals('delivered', $order->status);
        $this->assertEquals(812.04, $order->total_price);
        $this->assertEquals(812.04, $order->srp);
        $this->assertEquals(700.63, $order->estimated_cost);
        $this->assertEquals(2, $order->items_count);
        $this->assertEquals('SGD', $order->currency_code);
        $this->assertEquals('sale', $order->distribution_model);
        $this->assertNull($order->rejected_at);
        $this->assertNull($order->rejection_note);
    }

    public function test_get_status_enum(): void
    {
        $order = $this->makeOrder('fulfilled');
        $this->assertEquals(OrderStatus::FULFILLED, $order->getStatus());
    }

    public function test_all_order_statuses(): void
    {
        foreach (['pending', 'approved', 'rejected', 'fulfilled', 'delivered', 'cancelled'] as $status) {
            $order = $this->makeOrder($status);
            $this->assertEquals($status, $order->status);
            $this->assertInstanceOf(OrderStatus::class, $order->getStatus());
        }
    }
}
