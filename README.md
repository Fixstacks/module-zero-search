# Zero_Search

Store search that forgives typos and finds more than products.

- **Typos forgiven**: a search that finds nothing as typed runs once more
  with close spellings (OpenSearch fuzziness AUTO: a letter off up to 5
  letters, two beyond; the first letter right): "jaket" finds jackets. Exact
  results are never mixed with fuzzy ones. The results page says "Nothing
  matches "jaket" exactly; these are close spellings."; the search box's
  products are headed "Close spellings of …". Applies to the
  `quick_search_container` request (results page, layered navigation
  counts, the search box).
- **More than products**, in the search box's suggestions (3 of each) and
  above the results (a row of each): categories (with where they are, "Men ›
  Tops"), brands (Zero_Brands), blog posts (Zero_Blog), CMS pages. Each
  word of the query must be in the name. Another module adds its own:
  `Zero\Search\Model\SourceInterface`, registered on
  `Zero\Search\Model\Sources` in its `etc/frontend/di.xml`.
- **Popular searches**: the empty search box (on first focus) and a search
  with no results offer the store's most searched terms that found
  something (Marketing > Search Terms: untick "Display in Suggested Terms"
  to hide one).

Settings: Stores > Configuration > Zero > Search (typos, more results,
popular count). Synonyms and redirects for a term are Magento's own:
Marketing > SEO & Search > Search Synonyms, Search Terms.

The search box template is the Zero theme's
`Magento_Search/templates/form.mini.phtml`; the suggestions endpoint is
Zero_Core's `zero/search/suggest`, its template replaced here.

## Install

Requires Magento Open Source 2.4.7–2.4.9 and PHP 8.2–8.4 with the GD extension built with WebP (`php -r 'var_dump(function_exists("imagewebp"));'` says `true`; Magento's own PHP requirements cover the rest). Without WebP in GD, Zero skips its WebP image copies and serves the originals.

```bash
composer require fixstacks/module-zero-search
bin/magento setup:upgrade
bin/magento setup:di:compile           # production mode
bin/magento setup:static-content:deploy # production mode
bin/magento cache:flush
```

Composer installs `fixstacks/module-zero-core` with it.

Part of [`fixstacks/zero`](https://fixstacks.com/zero), which installs the Zero theme and every Zero module at once.

## Tests

`Test/Unit` (fuzzy clauses, sources); `dev/tests/zero-e2e/tests/search.spec.ts`
(`ZERO_SEARCH_TYPO`, `ZERO_SEARCH_CATEGORY` for a catalog other than Luma's).

## License

OSL-3.0 or AFL-3.0, your choice: see `NOTICE.md`.

---

<a href="https://vilmate.com"><picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/Fixstacks/zero/main/docs/images/vilmate-logo-dark.svg">
  <img src="https://raw.githubusercontent.com/Fixstacks/zero/main/docs/images/vilmate-logo.svg" alt="Vilmate" height="34" align="right">
</picture></a>

Zero is made by [Fixstacks](https://fixstacks.com/zero) in partnership with [Vilmate](https://vilmate.com), a software development company.
Documentation: https://fixstacks.com/zero/docs. Demo store: https://zero.fixstacks.com.
