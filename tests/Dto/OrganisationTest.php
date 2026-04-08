<?php

declare(strict_types=1);

namespace Ucubix\PhpClient\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Ucubix\PhpClient\Dto\CreditLine;
use Ucubix\PhpClient\Dto\Organisation;
use Ucubix\PhpClient\Dto\OrganisationSummary;

class OrganisationTest extends TestCase
{
    public function test_constructor_and_properties(): void
    {
        $summary = new OrganisationSummary(currencies: 3, total_usd_equivalent: '32336389.65');
        $creditLines = [
            new CreditLine(currency: 'USD', balance: '17179869.18'),
            new CreditLine(currency: 'SGD', balance: '17179869.18'),
        ];

        $org = new Organisation(
            uuid: '1f132dd6-8c2b-6ac6-ad8a-3e1e6bb58a9c',
            name: 'EpicSoft Asia Pte. Ltd.',
            summary: $summary,
            credit_lines: $creditLines,
        );

        $this->assertEquals('1f132dd6-8c2b-6ac6-ad8a-3e1e6bb58a9c', $org->uuid);
        $this->assertEquals('EpicSoft Asia Pte. Ltd.', $org->name);
        $this->assertEquals(3, $org->summary->currencies);
        $this->assertEquals('32336389.65', $org->summary->total_usd_equivalent);
        $this->assertCount(2, $org->credit_lines);
        $this->assertEquals('USD', $org->credit_lines[0]->currency);
    }
}
