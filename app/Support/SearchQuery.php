<?php

namespace App\Support;

/**
 * Turns what a person typed into a safe FTS5 MATCH expression.
 *
 * Raw input can't be passed to MATCH: quotes, hyphens, colons and words like OR are FTS syntax
 * and either error or change the meaning. Each word becomes a quoted prefix term, and all must match.
 */
class SearchQuery
{
    private const MAX_TERMS = 12;

    /**
     * The FTS5 expression, or null when nothing searchable is left.
     */
    public static function toMatch(?string $input): ?string
    {
        $terms = self::terms($input);

        if ($terms === []) {
            return null;
        }

        return implode(' ', array_map(fn (string $term) => '"'.$term.'"*', $terms));
    }

    /**
     * @return list<string>
     */
    public static function terms(?string $input): array
    {
        preg_match_all('/[\p{L}\p{N}_]+/u', mb_strtolower($input ?? ''), $matches);

        return array_slice(array_values(array_unique($matches[0])), 0, self::MAX_TERMS);
    }

    /**
     * Split FTS5 highlight()/snippet() output (marked with \x02 and \x03) into plain segments the
     * frontend renders as text, so no HTML from the database is ever injected.
     *
     * @return list<array{text: string, match: bool}>
     */
    public static function segments(string $marked): array
    {
        $segments = [];

        foreach (preg_split("/(\x02[^\x03]*\x03)/", $marked, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
            $match = str_starts_with($part, "\x02");
            $segments[] = ['text' => $match ? trim($part, "\x02\x03") : $part, 'match' => $match];
        }

        return $segments;
    }
}
