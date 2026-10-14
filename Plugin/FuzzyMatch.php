<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\Plugin;

use Magento\Elasticsearch\SearchAdapter\Query\Builder\MatchQuery;
use Magento\Framework\Search\Request\QueryInterface;
use Zero\Search\Model\Typos;

/**
 * While Typos says so, the word matches this builder adds (not quoted
 * phrases) allow a few wrong letters: OpenSearch's AUTO fuzziness (none up to
 * 2 letters, one up to 5, two beyond), the first letter exact.
 */
class FuzzyMatch
{
    public function __construct(private readonly Typos $typos)
    {
    }

    /**
     * @param array $result
     * @param array $selectQuery The query before this builder's clauses
     */
    public function afterBuild(MatchQuery $subject, array $result, array $selectQuery, QueryInterface $requestQuery, $conditionType): array
    {
        if (!$this->typos->isFuzzy()) {
            return $result;
        }
        foreach ($result['bool'] ?? [] as $condition => $clauses) {
            if ($condition === 'must_not' || !is_array($clauses)) {
                continue;
            }
            $before = count($selectQuery['bool'][$condition] ?? []);
            foreach (array_slice($clauses, $before, null, true) as $i => $clause) {
                if (!isset($clause['match']) || !is_array($clause['match'])) {
                    continue;
                }
                foreach ($clause['match'] as $field => $params) {
                    if (is_array($params) && is_string($params['query'] ?? null)) {
                        $result['bool'][$condition][$i]['match'][$field] += ['fuzziness' => 'AUTO', 'prefix_length' => 1, 'lenient' => true];
                    }
                }
            }
        }

        return $result;
    }
}
