<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\Model;

use Psr\Log\LoggerInterface;

/**
 * The sources (etc/frontend/di.xml), each searched for the query; the ones
 * that find something, in their sort order.
 */
class Sources
{
    /**
     * @param array<string, array{source: SourceInterface, sortOrder?: int}> $sources
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly array $sources = []
    ) {
    }

    /**
     * @return array<string, array{title: string, items: array<int, array{name: string, url: string}>}>
     */
    public function find(string $query, int $storeId, int $limit): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $sources = $this->sources;
        uasort($sources, static fn ($a, $b) => ($a['sortOrder'] ?? 0) <=> ($b['sortOrder'] ?? 0));
        $found = [];
        foreach ($sources as $code => $entry) {
            $source = $entry['source'] ?? null;
            if (!$source instanceof SourceInterface) {
                continue;
            }
            try {
                $items = $source->find($query, $storeId, $limit);
            } catch (\Throwable $e) {
                $this->logger->warning(sprintf('Zero_Search: source %s: %s', $code, $e->getMessage()));
                continue;
            }
            if ($items) {
                $found[$code] = ['title' => $source->title(), 'items' => array_slice($items, 0, $limit)];
            }
        }

        return $found;
    }

    /**
     * For SQL LIKE: the query's words, each to be contained.
     *
     * @return string[]
     */
    public static function likePatterns(string $query): array
    {
        $words = preg_split('/\s+/u', trim($query)) ?: [];

        return array_map(static fn ($word) => '%' . addcslashes($word, '%_\\') . '%', array_filter($words, 'strlen'));
    }
}
