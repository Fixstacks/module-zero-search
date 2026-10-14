<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\Model;

/**
 * Something a store search finds besides products (categories, pages,
 * brands, posts): a heading and the matches, best first; a match's note
 * tells it apart (where a category is).
 */
interface SourceInterface
{
    public function title(): string;

    /**
     * @return array<int, array{name: string, url: string, note?: string}>
     */
    public function find(string $query, int $storeId, int $limit): array;
}
