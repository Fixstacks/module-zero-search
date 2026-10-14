<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\Model\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Zero\Search\Model\SourceInterface;
use Zero\Search\Model\Sources;

/**
 * The store's active categories whose name has every word of the query,
 * each with where it is ("Men › Tops": several categories share a name).
 */
class Categories implements SourceInterface
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function title(): string
    {
        return (string) __('Categories');
    }

    public function find(string $query, int $storeId, int $limit): array
    {
        $rootId = (int) $this->storeManager->getStore($storeId)->getRootCategoryId();
        $collection = $this->collectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect(['name', 'url_key', 'url_path'])
            ->addAttributeToFilter('is_active', 1)
            ->addFieldToFilter('path', ['like' => '1/' . $rootId . '/%'])
            ->addUrlRewriteToResult()
            ->setOrder('level', 'ASC')
            ->setOrder('position', 'ASC')
            ->setPageSize($limit);
        foreach (Sources::likePatterns($query) as $pattern) {
            $collection->addAttributeToFilter('name', ['like' => $pattern]);
        }
        $categories = $collection->getItems();
        $ancestors = [];
        foreach ($categories as $category) {
            $ancestors = array_merge($ancestors, array_slice(explode('/', (string) $category->getPath()), 2, -1));
        }
        $names = $this->names(array_unique(array_map('intval', $ancestors)), $storeId);
        $items = [];
        foreach ($categories as $category) {
            $path = array_slice(explode('/', (string) $category->getPath()), 2, -1);
            $items[] = [
                'name' => (string) $category->getName(),
                'url' => (string) $category->getUrl(),
                'note' => implode(' › ', array_filter(array_map(static fn ($id) => $names[(int) $id] ?? '', $path))),
            ];
        }

        return $items;
    }

    /**
     * @param int[] $ids
     * @return array<int, string>
     */
    private function names(array $ids, int $storeId): array
    {
        if (!$ids) {
            return [];
        }
        $names = [];
        $collection = $this->collectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect('name')
            ->addFieldToFilter('entity_id', ['in' => $ids]);
        foreach ($collection as $category) {
            $names[(int) $category->getId()] = (string) $category->getName();
        }

        return $names;
    }
}
