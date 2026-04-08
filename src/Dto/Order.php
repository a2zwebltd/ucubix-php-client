<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Dto;

use Spatie\LaravelData\Data;

use Ucubix\PhpClient\Enums\OrderStatus;

class Order extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly ?string $external_reference,
        public readonly ?int $external_reference_attempt,
        public readonly string $status,
        public readonly float $total_price,
        public readonly float $srp,
        public readonly ?float $estimated_cost,
        public readonly int $items_count,
        public readonly ?string $currency_code,
        public readonly string $order_date,
        public readonly ?string $approved_at,
        public readonly ?string $rejected_at,
        public readonly ?string $delivered_at,
        public readonly ?string $distribution_model,
        public readonly ?string $rejection_note,
    ) {}

    public function getStatus(): OrderStatus
    {
        return OrderStatus::from($this->status);
    }
}
