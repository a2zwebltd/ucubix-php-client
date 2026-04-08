<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Ucubix\PhpClient\Dto\Category;
use Ucubix\PhpClient\Dto\Developer;
use Ucubix\PhpClient\Dto\Franchise;
use Ucubix\PhpClient\Dto\Media;
use Ucubix\PhpClient\Dto\Platform;
use Ucubix\PhpClient\Dto\Publisher;

class DictionaryDtoTest extends TestCase
{
    public function test_category(): void
    {
        $cat = new Category(id: 'cat-1', name: 'Players', parent_id: null, child_ids: ['c1', 'c2']);

        $this->assertEquals('cat-1', $cat->id);
        $this->assertEquals('Players', $cat->name);
        $this->assertNull($cat->parent_id);
        $this->assertCount(2, $cat->child_ids);
    }

    public function test_publisher(): void
    {
        $pub = new Publisher(id: 'p1', name: 'Big Publisher', website: 'https://pub.com', about: 'A publisher', created_at: '2024-01-01T00:00:00Z', updated_at: '2024-06-01T00:00:00Z');

        $this->assertEquals('p1', $pub->id);
        $this->assertEquals('Big Publisher', $pub->name);
        $this->assertEquals('https://pub.com', $pub->website);
    }

    public function test_developer(): void
    {
        $dev = new Developer(id: 'd1', name: 'Indie Studio', website: null, about: null, created_at: '2024-01-01T00:00:00Z', updated_at: '2024-06-01T00:00:00Z');

        $this->assertEquals('d1', $dev->id);
        $this->assertNull($dev->website);
    }

    public function test_platform(): void
    {
        $plat = new Platform(id: 'pl1', name: 'Steam', created_at: '2024-01-01T00:00:00Z', updated_at: '2024-06-01T00:00:00Z');

        $this->assertEquals('Steam', $plat->name);
    }

    public function test_franchise(): void
    {
        $fr = new Franchise(id: 'f1', name: 'Test Franchise', created_at: '2024-01-01T00:00:00Z');

        $this->assertEquals('f1', $fr->id);
    }

    public function test_media(): void
    {
        $media = new Media(id: 'm1', name: 'cover.jpg', file_name: 'cover.jpg', collection_name: 'photos', mime_type: 'image/jpeg', disk: 's3', size: 102400, order_column: 1, url: 'https://img.com/cover.jpg', created_at: '2024-01-01T00:00:00Z', updated_at: '2024-06-01T00:00:00Z');

        $this->assertEquals('m1', $media->id);
        $this->assertEquals('image/jpeg', $media->mime_type);
        $this->assertEquals(102400, $media->size);
        $this->assertEquals('https://img.com/cover.jpg', $media->url);
    }
}
