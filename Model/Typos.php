<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Search\Model;

/**
 * This request's typo state: whether the query being built matches its words
 * fuzzily (Plugin\FuzzyMatch), and whether a search found anything only that
 * way (the results page then says so).
 */
class Typos
{
    private bool $fuzzy = false;

    private bool $forgiven = false;

    public function isFuzzy(): bool
    {
        return $this->fuzzy;
    }

    public function setFuzzy(bool $fuzzy): void
    {
        $this->fuzzy = $fuzzy;
    }

    public function wereForgiven(): bool
    {
        return $this->forgiven;
    }

    public function forgave(): void
    {
        $this->forgiven = true;
    }
}
