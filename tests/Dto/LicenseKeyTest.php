<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Ucubix\PhpClient\Dto\LicenseKey;

class LicenseKeyTest extends TestCase
{
    public function test_constructor_and_properties(): void
    {
        $key = new LicenseKey(
            id: 'lk-uuid-123',
            license_key: 'ABCD-EFGH-IJKL-MNOP',
            created_at: '2024-01-15T10:30:00.000000Z',
            updated_at: '2024-01-15T10:30:00.000000Z',
        );

        $this->assertEquals('lk-uuid-123', $key->id);
        $this->assertEquals('ABCD-EFGH-IJKL-MNOP', $key->license_key);
    }
}
