<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\Test\Unit\Plugin;

use Magento\Elasticsearch\SearchAdapter\Query\Builder\MatchQuery;
use Magento\Framework\Search\Request\QueryInterface;
use PHPUnit\Framework\TestCase;
use Zero\Search\Model\Typos;
use Zero\Search\Plugin\FuzzyMatch;

class FuzzyMatchTest extends TestCase
{
    private const BEFORE = ['bool' => ['should' => [['match' => ['sku' => ['query' => 'old']]]]]];

    private function built(): array
    {
        $result = self::BEFORE;
        $result['bool']['should'][] = ['match' => ['_search' => ['query' => 'jaket', 'boost' => 2]]];
        $result['bool']['should'][] = ['match_phrase' => ['name' => ['query' => 'jaket']]];

        return $result;
    }

    private function after(bool $fuzzy): array
    {
        $typos = new Typos();
        $typos->setFuzzy($fuzzy);

        return (new FuzzyMatch($typos))->afterBuild(
            $this->createMock(MatchQuery::class),
            $this->built(),
            self::BEFORE,
            $this->createMock(QueryInterface::class),
            'should'
        );
    }

    public function testLeavesTheQueryAloneUnlessFuzzy(): void
    {
        $this->assertSame($this->built(), $this->after(false));
    }

    public function testOnlyThisBuildersWordMatchesGetFuzzy(): void
    {
        $result = $this->after(true);
        $this->assertSame(['query' => 'old'], $result['bool']['should'][0]['match']['sku']);
        $this->assertSame(
            ['query' => 'jaket', 'boost' => 2, 'fuzziness' => 'AUTO', 'prefix_length' => 1, 'lenient' => true],
            $result['bool']['should'][1]['match']['_search']
        );
        $this->assertSame(['query' => 'jaket'], $result['bool']['should'][2]['match_phrase']['name']);
    }
}
