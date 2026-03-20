<?php

declare(strict_types=1);

namespace LidingoCustomisation\Search;

class SearchTextFormatter
{
    /** Split a keyword string into unique search terms ordered by length. */
    public function getSearchTerms(string $keyword): array
    {
        $terms = preg_split('/\s+/u', $keyword) ?: [];

        $terms = array_values(array_filter(
            array_map(
                static fn(string $term): string => trim($term),
                $terms
            ),
            static fn(string $term): bool => $term !== ''
        ));

        usort(
            $terms,
            static fn(string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left)
        );

        return array_unique($terms);
    }

    /** Highlight matching terms in a plain-text string. */
    public function highlightText(string $text, array $searchTerms): string
    {
        $highlightedText = esc_html($text);

        foreach ($searchTerms as $term) {
            $escapedTerm = preg_quote(esc_html($term), '/');
            $highlightedText = preg_replace(
                '/(' . $escapedTerm . ')/iu',
                '<mark>$1</mark>',
                $highlightedText
            ) ?? $highlightedText;
        }

        return wp_kses($highlightedText, ['mark' => []]);
    }

    /** Build a highlighted excerpt from the best available source text. */
    public function buildHighlightedExcerpt(string $excerpt, string $content, array $searchTerms): string
    {
        $sourceText = $this->resolveExcerptSource($excerpt, $content, $searchTerms);

        if ($sourceText === '') {
            return '';
        }

        $snippet = $this->buildSnippet($sourceText, $searchTerms);

        return $this->highlightText($snippet, $searchTerms);
    }

    /** Normalize text before matching or displaying it in search results. */
    public function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = wp_strip_all_tags($text, true);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /** Prefer the first non-placeholder text source that contains a search term. */
    private function resolveExcerptSource(string $excerpt, string $content, array $searchTerms): string
    {
        $missingContentPlaceholders = array_values(array_filter(array_unique([
            $this->normalizeText(__('Item is missing content', 'municipio')),
            $this->normalizeText(__('Objektet saknar innehåll', 'municipio')),
        ])));
        $sources = [
            $this->normalizeText($excerpt),
            $this->normalizeText($content),
        ];

        foreach ($sources as $source) {
            if ($source === '') {
                continue;
            }

            if (in_array($source, $missingContentPlaceholders, true)) {
                continue;
            }

            if ($this->containsSearchTerm($source, $searchTerms)) {
                return $source;
            }
        }

        foreach ($sources as $source) {
            if ($source !== '' && !in_array($source, $missingContentPlaceholders, true)) {
                return $source;
            }
        }

        return '';
    }

    /** Trim the source text to a short snippet around the first match. */
    private function buildSnippet(string $text, array $searchTerms): string
    {
        if ($text === '') {
            return '';
        }

        if (empty($searchTerms)) {
            return wp_trim_words($text, 32, '...');
        }

        $matchPosition = $this->findFirstMatchPosition($text, $searchTerms);

        if ($matchPosition === null) {
            return wp_trim_words($text, 32, '...');
        }

        $snippetLength = 220;
        $start = max(0, $matchPosition - 60);
        $snippet = mb_substr($text, $start, $snippetLength);
        $needsPrefix = $start > 0;
        $needsSuffix = ($start + mb_strlen($snippet)) < mb_strlen($text);

        $snippet = trim($snippet);

        if ($needsPrefix) {
            $snippet = '...' . ltrim($snippet);
        }

        if ($needsSuffix) {
            $snippet = rtrim($snippet, ' .,') . '...';
        }

        return $snippet;
    }

    /** Find the earliest position of any search term within the text. */
    private function findFirstMatchPosition(string $text, array $searchTerms): ?int
    {
        $firstMatch = null;

        foreach ($searchTerms as $term) {
            $position = mb_stripos($text, $term);

            if ($position === false) {
                continue;
            }

            if ($firstMatch === null || $position < $firstMatch) {
                $firstMatch = $position;
            }
        }

        return $firstMatch;
    }

    /** Check whether the text contains at least one search term. */
    private function containsSearchTerm(string $text, array $searchTerms): bool
    {
        return $this->findFirstMatchPosition($text, $searchTerms) !== null;
    }
}
