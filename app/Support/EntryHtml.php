<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * The one place editor HTML is cleaned and turned into searchable text.
 *
 * The allow-list mirrors what the TipTap editor can produce; anything else is dropped.
 */
class EntryHtml
{
    private const MAX_INPUT_LENGTH = 500_000;

    private const BLOCK_TAGS = ['p', 'h2', 'h3', 'h4', 'li', 'pre', 'blockquote', 'br', 'hr', 'img'];

    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(?string $html): string
    {
        return self::sanitizer()->sanitize($html ?? '');
    }

    /**
     * The first image in a (sanitized) body, used as the reading view's feature image.
     */
    public static function firstImage(string $html): ?string
    {
        return preg_match('#<img[^>]+src="([^"]+)"#i', $html, $match)
            ? html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : null;
    }

    /**
     * Reading time at 265 words a minute, the rate Ghost uses, rounded up; never zero.
     */
    public static function readingMinutes(string $text): int
    {
        return max(1, (int) ceil(str_word_count($text) / 265));
    }

    /**
     * Plain text from a non-editor source (MCP, imports) as escaped editor HTML:
     * blank lines start a new paragraph, single newlines become <br>.
     */
    public static function fromText(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));

        if ($text === '') {
            return '';
        }

        return collect(preg_split("/\n\s*\n/", $text))
            ->map(fn (string $paragraph) => '<p>'.collect(explode("\n", trim($paragraph, "\n")))
                ->map(fn (string $line) => e(rtrim($line)))
                ->implode('<br>').'</p>')
            ->implode('');
    }

    /**
     * Plain text for the FTS index: block boundaries become line breaks so words don't run together.
     */
    public static function toText(string $html): string
    {
        $pattern = '#<(/?(?:'.implode('|', self::BLOCK_TAGS).'))(\s[^>]*)?/?>#i';
        $spaced = preg_replace($pattern, "\n$0", $html);
        $text = html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = array_map(
            fn (string $line) => trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $line)),
            explode("\n", $text),
        );

        return trim(preg_replace("/\n{2,}/", "\n", implode("\n", $lines)));
    }

    /**
     * Root-relative upload paths referenced by <img> tags, e.g. "attachments/2026/11/abc.png".
     *
     * @return list<string>
     */
    public static function attachmentPaths(string $html): array
    {
        preg_match_all('#<img[^>]+src="/storage/(attachments/[^"]+)"#i', $html, $matches);

        return array_values(array_unique($matches[1]));
    }

    private static function sanitizer(): HtmlSanitizer
    {
        if (self::$sanitizer) {
            return self::$sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->withMaxInputLength(self::MAX_INPUT_LENGTH)
            ->allowElement('a', ['href'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowRelativeLinks()
            ->allowElement('img', ['src', 'alt'])
            ->allowMediaSchemes(['https', 'http'])
            ->allowRelativeMedias()
            ->allowElement('code', ['class']);

        foreach (['p', 'br', 'hr', 'strong', 'em', 'u', 's', 'pre', 'blockquote', 'ul', 'ol', 'li', 'h2', 'h3', 'h4'] as $element) {
            $config = $config->allowElement($element);
        }

        return self::$sanitizer = new HtmlSanitizer($config);
    }
}
