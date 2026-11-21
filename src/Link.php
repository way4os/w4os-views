<?php

namespace W4OS\Views;

/**
 * A link of a site, its kind its class, the same in every bridge:
 *
 *     Link::to($urls, '/news', 'All news', 'more');   // <a class="w4os-more" href="/fr/news">All news</a>
 *     Link::html('/fr/news', 'All news', 'more');      // the same, its address built already
 *
 * $urls builds the address for the page shown (the engine's W4OS\Engine\Http\Url: the base of the site, the language of the
 * visitor, never its prefix twice). The kinds: `plain`, `more` (a link to more: on its own line, at the right, a chevron before it),
 * `button` and `primary` (a button of the theme, the one to click first). The text is escaped, the address too.
 */
final class Link
{
    /** The classes of each kind of link. */
    public const KINDS = [
        'plain' => '',
        'more' => 'w4os-more',
        'button' => 'btn btn-ghost',
        'primary' => 'btn btn-primary',
    ];

    /**
     * A link to a target of the site, its address built by $urls for the page shown.
     *
     * @param object $urls what builds the addresses of the site: build(string $target, array $args): string
     * @param array<string,mixed> $args the arguments of the link
     * @param string $class classes of its own, after the ones of its kind
     */
    public static function to(object $urls, string $target, string $text, string $kind = 'plain', array $args = [], string $class = ''): string
    {
        return self::html((string) $urls->build($target, $args), $text, $kind, $class);
    }

    /** A link to an address built already, its kind its class (KINDS: plain for one it does not know). */
    public static function html(string $href, string $text, string $kind = 'plain', string $class = ''): string
    {
        $classes = trim((self::KINDS[$kind] ?? '').' '.$class);

        return '<a'.($classes === '' ? '' : ' class="'.self::escape($classes).'"').' href="'.self::escape($href).'">'.self::escape($text).'</a>';
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
