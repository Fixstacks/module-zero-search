<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Zero\Search\Model\SourceInterface;
use Zero\Search\Model\Sources;

class SourcesTest extends TestCase
{
    private function source(string $title, array $items): SourceInterface
    {
        return new class ($title, $items) implements SourceInterface {
            public function __construct(private string $title, private array $items)
            {
            }

            public function title(): string
            {
                return $this->title;
            }

            public function find(string $query, int $storeId, int $limit): array
            {
                if ($this->items === ['fail']) {
                    throw new \RuntimeException('down');
                }

                return $this->items;
            }
        };
    }

    public function testSortedNonEmptyLimitedAndAFailingSourceSkipped(): void
    {
        $item = ['name' => 'Tees', 'url' => '/tees'];
        $sources = new Sources($this->createMock(LoggerInterface::class), [
            'pages' => ['source' => $this->source('Pages', [$item, $item, $item]), 'sortOrder' => 40],
            'broken' => ['source' => $this->source('Broken', ['fail']), 'sortOrder' => 5],
            'empty' => ['source' => $this->source('Empty', []), 'sortOrder' => 1],
            'categories' => ['source' => $this->source('Categories', [$item]), 'sortOrder' => 10],
        ]);
        $found = $sources->find(' tee ', 1, 2);
        $this->assertSame(['categories', 'pages'], array_keys($found));
        $this->assertCount(2, $found['pages']['items']);
        $this->assertSame([], $sources->find('  ', 1, 2));
    }

    public function testLikePatternsEscapeWildcards(): void
    {
        $this->assertSame(['%50\%%', '%a\_b%'], Sources::likePatterns(' 50%  a_b '));
    }
}
