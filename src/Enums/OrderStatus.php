<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Enums;

enum OrderStatus: string
{
    case NEW = 'new';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case FULFILLED = 'fulfilled';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
