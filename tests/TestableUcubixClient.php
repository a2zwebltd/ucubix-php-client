<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Tests;

use GuzzleHttp\Client as GuzzleClient;
use Ucubix\PhpClient\Client\UcubixClient;

class TestableUcubixClient extends UcubixClient
{
    public function __construct(GuzzleClient $mockClient)
    {
        parent::__construct(apiKey: 'test-api-key');

        $reflection = new \ReflectionProperty(UcubixClient::class, 'httpClient');
        $reflection->setValue($this, $mockClient);
    }
}
