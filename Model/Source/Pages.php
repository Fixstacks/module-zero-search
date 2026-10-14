<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\Model\Source;

use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\UrlInterface;
use Zero\Search\Model\SourceInterface;
use Zero\Search\Model\Sources;

/**
 * The store's active CMS pages whose title has every word of the query (not
 * the 404 page).
 */
class Pages implements SourceInterface
{
    private const SKIP = ['no-route'];

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly UrlInterface $url
    ) {
    }

    public function title(): string
    {
        return (string) __('Pages');
    }

    public function find(string $query, int $storeId, int $limit): array
    {
        $collection = $this->collectionFactory->create()
            ->addStoreFilter($storeId)
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('identifier', ['nin' => self::SKIP])
            ->setPageSize($limit);
        foreach (Sources::likePatterns($query) as $pattern) {
            $collection->addFieldToFilter('title', ['like' => $pattern]);
        }
        $items = [];
        foreach ($collection as $page) {
            $items[] = ['name' => (string) $page->getTitle(), 'url' => $this->url->getUrl('', ['_direct' => (string) $page->getIdentifier()])];
        }

        return $items;
    }
}
