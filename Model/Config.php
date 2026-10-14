<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function forgivesTypos(): bool
    {
        return $this->scopeConfig->isSetFlag('zero_search/general/typo_tolerance', ScopeInterface::SCOPE_STORE);
    }

    public function showsMoreResults(): bool
    {
        return $this->scopeConfig->isSetFlag('zero_search/general/more_results', ScopeInterface::SCOPE_STORE);
    }

    public function popularCount(): int
    {
        return max(0, (int) $this->scopeConfig->getValue('zero_search/general/popular_count', ScopeInterface::SCOPE_STORE));
    }
}
