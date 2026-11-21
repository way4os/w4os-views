<?php

namespace W4OS\Views;

/**
 * A teleport link that fits where it is seen, the same anywhere a site prints one: its main link (the first the destination gives
 * for that client), then a small chevron opening a short menu of its other links for that client and "Copy the address".
 *
 * Inline through and through, as a link is: in a sentence, a table cell, a list or a heading, the chevron a suffix of its text
 * (never on a line of its own), the menu laid over the page from it. Its content may be markup of the caller's ($html: a
 * picture and a name), where it goes on the page the caller's own style.
 *
 * The links of both clients are written (a page kept in a cache is seen from a browser and from the viewer alike): its script
 * (ASSETS) tells the client from the user agent (data-viewer-pattern) and shows its links, the main one's address too; without it,
 * the client the server told (data-client).
 *
 *     TeleportLink::html(['browser' => $browser, 'viewer' => $viewer], 'Grand Place', 'browser', $texts, $pattern);
 *     TeleportLink::html($links, 'Grand Place', 'browser', $texts, $pattern, '<img src="..." alt=""> Grand Place');
 *
 * Each link: `format` (its Destination format), `link`, `label` (its text in a menu, translated); the address to copy is the one
 * of format COPY (1024, host:port:Region). $texts: `more` (the label of the button), `copied` (said once copied).
 */
final class TeleportLink
{
    /** The format of the address to copy, host:port:Region as the map of a viewer takes it (Destination::LSL of the engine). */
    public const COPY = 1024;

    /** The clients a destination gives links for. */
    public const CLIENTS = ['browser', 'viewer'];

    /** Its style and its script, in assets/ of this package: a bridge serves them on the pages that show teleport links. */
    public const ASSETS = ['css' => ['css/tp-link.css'], 'js' => ['js/tp-link.js']];

    /** The chevron of its menu (Tabler's chevron-down), at the size and in the colour of the text. */
    private const CHEVRON = '<svg class="w4os-tp-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 9l6 6l6 -6"/></svg>';

    /**
     * @param array{browser?: list<array{format:int,link:string,label:string}>, viewer?: list<array{format:int,link:string,label:string}>} $links
     * @param array{more?: string, copied?: string} $texts
     * @param string|null $html the content of the main link in place of its label, markup the caller has escaped (a picture and
     *                          a name)
     */
    public static function html(array $links, string $label, string $client = 'browser', array $texts = [], string $pattern = '', ?string $html = null): string
    {
        $client = in_array($client, self::CLIENTS, true) ? $client : 'browser';
        $content = $html ?? self::e($label);
        $copy = null;
        $main = [];
        $items = '';
        foreach (self::CLIENTS as $for) {
            $own = array_values(array_filter((array) ($links[$for] ?? []), fn ($link) => is_array($link) && (string) ($link['link'] ?? '') !== ''));
            foreach ($own as $link) {
                if ((int) ($link['format'] ?? 0) === self::COPY) {
                    $copy ??= $link;
                    continue;
                }
                if (! isset($main[$for])) {
                    $main[$for] = (string) $link['link'];
                    continue;
                }
                $items .= '<a role="menuitem" class="w4os-tp-item" data-for="'.$for.'" href="'.self::e((string) $link['link']).'">'.self::e((string) ($link['label'] ?? '')).'</a>';
            }
        }
        if ($main === [] && $copy === null) {
            return $content;
        }
        // The main link of the client told, none where that client has none (a grid alone in a browser): the text, the script giving
        // it the address of the client it finds
        $href = $main[$client] ?? '';
        $first = $main === []
            ? '<span class="w4os-tp-main">'.$content.'</span>'
            : '<a class="w4os-tp-main"'.($href === '' ? '' : ' href="'.self::e($href).'"').self::data('browser', $main['browser'] ?? '').self::data('viewer', $main['viewer'] ?? '').'>'.$content.'</a>';
        if ($copy !== null) {
            $items .= '<button type="button" role="menuitem" class="w4os-tp-item w4os-tp-copy" data-w4os-tp-copy="'.self::e((string) $copy['link']).'" data-copied="'.self::e((string) ($texts['copied'] ?? '')).'">'.self::e((string) ($copy['label'] ?? '')).'</button>';
        }
        $more = (string) ($texts['more'] ?? '');

        // The chevron joined to the text (a word joiner: no line break between them), the menu placed from it
        return '<span class="w4os-tp" data-w4os-tp data-client="'.$client.'"'.($pattern === '' ? '' : ' data-viewer-pattern="'.self::e($pattern).'"').'>'.$first
            .($items === '' ? '' : '&#8288;<span class="w4os-tp-more"><button type="button" class="w4os-tp-toggle" aria-haspopup="true" aria-expanded="false" aria-label="'.self::e($more).'" title="'.self::e($more).'">'.self::CHEVRON.'</button>'
                .'<span class="w4os-tp-menu" role="menu" hidden>'.$items.'</span></span>')
            .'</span>';
    }

    private static function data(string $client, string $href): string
    {
        return $href === '' ? '' : ' data-'.$client.'="'.self::e($href).'"';
    }

    private static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
