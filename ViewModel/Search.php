<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\ViewModel;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Search\Helper\Data as SearchHelper;
use Magento\Search\Model\QueryFactory;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory as QueryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Zero\Search\Model\Config;
use Zero\Search\Model\Sources;
use Zero\Search\Model\Typos;

/**
 * Besides products, for the search box's suggestions and the results page:
 * what else the query finds (categories, brands, posts, pages), the store's
 * popular searches, and whether typos were forgiven.
 */
class Search implements ArgumentInterface
{
    /** In the search box: a few of each; above the results: a row. */
    public const SUGGEST_LIMIT = 3;
    public const RESULTS_LIMIT = 8;

    public function __construct(
        private readonly Config $config,
        private readonly Sources $sources,
        private readonly Typos $typos,
        private readonly QueryFactory $queryFactory,
        private readonly QueryCollectionFactory $queryCollectionFactory,
        private readonly SearchHelper $searchHelper,
        private readonly StoreManagerInterface $storeManager,
        private readonly LayerResolver $layerResolver,
        private readonly LayoutInterface $layout
    ) {
    }

    public function queryText(): string
    {
        return trim((string) $this->queryFactory->get()->getQueryText());
    }

    /**
     * @return array<string, array{title: string, items: array<int, array{name: string, url: string}>}>
     */
    public function more(int $limit): array
    {
        if (!$this->config->showsMoreResults() || $this->searchHelper->isMinQueryLength()) {
            return [];
        }

        return $this->sources->find($this->queryText(), $this->storeId(), $limit);
    }

    /**
     * The store's most searched terms that found something.
     *
     * @return array<int, array{title: string, url: string}>
     */
    public function popular(): array
    {
        $count = $this->config->popularCount();
        if (!$count) {
            return [];
        }
        $collection = $this->queryCollectionFactory->create()
            ->setPopularQueryFilter($this->storeId());
        $collection->addFieldToFilter('main_table.display_in_terms', 1)->setPageSize($count);
        $terms = [];
        foreach ($collection as $query) {
            $title = trim((string) $query->getQueryText());
            if ($title !== '' && $title !== $this->queryText()) {
                $terms[] = ['title' => $title, 'url' => $this->searchHelper->getResultUrl($title)];
            }
        }

        return $terms;
    }

    /**
     * Whether the results shown are close spellings of the query, found only
     * that way.
     */
    public function typosForgiven(): bool
    {
        return $this->typos->wereForgiven();
    }

    /**
     * The results count, needed before the list renders (typo state). The
     * full-text collection searches once, with the paging and sort set at that
     * moment, so this sets up the list's toolbar as ListProduct does and then
     * counts: one search, paged (counting earlier got every result; a clone
     * searched twice).
     */
    public function resultCount(): int
    {
        $list = $this->layout->getBlock('search_result_list');
        if (!$list instanceof ListProduct) {
            return (int) (clone $this->layerResolver->get()->getProductCollection())->getSize();
        }
        $collection = $list->getLoadedProductCollection();
        $toolbar = $list->getToolbarBlock();
        if ($orders = $list->getAvailableOrders()) {
            $toolbar->setAvailableOrders($orders);
        }
        if ($sort = $list->getSortBy()) {
            $toolbar->setDefaultOrder($sort);
        }
        if ($direction = $list->getDefaultDirection()) {
            $toolbar->setDefaultDirection($direction);
        }
        if ($modes = $list->getModes()) {
            $toolbar->setModes($modes);
        }
        $toolbar->setCollection($collection);

        return (int) $collection->getSize();
    }

    private function storeId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }
}
