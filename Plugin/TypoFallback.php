<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\Plugin;

use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\ResponseInterface;
use Magento\Search\Model\SearchEngine;
use Zero\Search\Model\Config;
use Zero\Search\Model\Typos;

/**
 * A store search (the results page, the search box's products) that finds
 * nothing as typed runs once more with its words matched fuzzily: one or two
 * letters off, the first one right ("jaket" → jacket). Exact matches never
 * mix with fuzzy ones: only a search with no results is retried.
 */
class TypoFallback
{
    private const REQUESTS = ['quick_search_container'];

    public function __construct(
        private readonly Typos $typos,
        private readonly Config $config
    ) {
    }

    public function aroundSearch(SearchEngine $subject, callable $proceed, RequestInterface $request): ResponseInterface
    {
        $response = $proceed($request);
        if ($this->typos->isFuzzy()
            || !in_array($request->getName(), self::REQUESTS, true)
            || $this->total($response) > 0
            || !$this->config->forgivesTypos()
        ) {
            return $response;
        }
        $this->typos->setFuzzy(true);
        try {
            $fuzzy = $proceed($request);
        } finally {
            $this->typos->setFuzzy(false);
        }
        if ($this->total($fuzzy) === 0) {
            return $response;
        }
        $this->typos->forgave();

        return $fuzzy;
    }

    private function total(ResponseInterface $response): int
    {
        return method_exists($response, 'getTotal') ? (int) $response->getTotal() : count($response);
    }
}
