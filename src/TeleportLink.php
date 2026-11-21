<?php

namespace W4OS\Views;

/**
 * A teleport link that fits where it is seen, the same anywhere a site prints one: its main link (the first the destination gives
 * for that client), then what else it can do for that client. One thing only: its icon after the text, no menu (the door of a
 * teleport, the map, or the copy button where the address is all there is). More: a chevron opening the menu of all of them, the
 * main link first and marked, each teleport with its door, then "Show on the map", then "Copy the address".
 *
 * Inline through and through, as a link is: in a sentence, a table cell, a list or a heading, the icon a suffix of its text
 * (never on a line of its own), the menu laid over the page from it. Its content may be markup of the caller's ($html: a
 * picture and a name), where it goes on the page the caller's own style.
 *
 * The links of both clients are written (a page kept in a cache is seen from a browser and from the viewer alike): its script
 * (ASSETS) tells the client from the user agent (data-viewer-pattern) and shows its links, the main one's address too; without it,
 * the client the server told (data-client). A client has its own links or none, never the other's.
 *
 *     TeleportLink::html(['browser' => $browser, 'viewer' => $viewer], 'Grand Place', 'browser', $texts, $pattern);
 *     TeleportLink::html($links, 'Grand Place', 'browser', $texts, $pattern, '<img src="..." alt=""> Grand Place');
 *
 * Each link: `action` (teleport, map or copy: what it does), `format` (its Destination format), `link`, `label` (its text in a
 * menu, translated). Without an action (an engine before them), the format tells it: COPY (1024, host:port:Region) the address
 * to copy, MAP (64) the map, any other a teleport. $texts: `more` (the label of the chevron), `copied` (said once copied).
 */
final class TeleportLink
{
    /** The format of the address to copy, host:port:Region as the map of a viewer takes it (Destination::LSL of the engine). */
    public const COPY = 1024;

    /** The format of the map of the viewer at the place (Destination::MAP of the engine). */
    public const MAP = 64;

    /** The clients a destination gives links for. */
    public const CLIENTS = ['browser', 'viewer'];

    /** Its style and its script, in assets/ of this package: a bridge serves them on the pages that show teleport links. */
    public const ASSETS = ['css' => ['css/tp-link.css'], 'js' => ['js/tp-link.js']];

    /** Its icons (Tabler 3.52: chevron-down, door-enter, map, copy, check), at the size and in the colour of the text. */
    private const ICONS = [
        'more' => '<path d="M6 9l6 6l6 -6"/>',
        'go' => '<path d="M13 12v.01"/><path d="M3 21h18"/><path d="M5 21v-16a2 2 0 0 1 2 -2h6m4 10.5v7.5"/><path d="M21 7h-7m3 -3l-3 3l3 3"/>',
        'map' => '<path d="M3 7l6 -3l6 3l6 -3v13l-6 3l-6 -3l-6 3v-13"/><path d="M9 4v13"/><path d="M15 7v13"/>',
        'copy' => '<path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666"/><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1"/>',
        'done' => '<path d="M5 12l5 5l10 -10"/>',
    ];

    /**
     * @param array{browser?: list<array{action?:string,format?:int,link:string,label:string}>, viewer?: list<array{action?:string,format?:int,link:string,label:string}>} $links
     * @param array{more?: string, copied?: string} $texts
     * @param string|null $html the content of the main link in place of its label, markup the caller has escaped (a picture and
     *                          a name)
     */
    public static function html(array $links, string $label, string $client = 'browser', array $texts = [], string $pattern = '', ?string $html = null): string
    {
        $client = in_array($client, self::CLIENTS, true) ? $client : 'browser';
        $content = $html ?? self::e($label);
        // The teleports of each client, its map and its address to copy
        $go = $map = $copy = [];
        foreach (self::CLIENTS as $for) {
            foreach ((array) ($links[$for] ?? []) as $link) {
                if (! is_array($link) || (string) ($link['link'] ?? '') === '') {
                    continue;
                }
                $action = self::action($link);
                if ($action === 'copy') {
                    $copy[$for] ??= $link;
                } elseif ($action === 'map') {
                    $map[$for][] = $link;
                } else {
                    $go[$for][] = $link;
                }
            }
        }
        $count = fn (string $for) => count($go[$for] ?? []) + count($map[$for] ?? []) + (isset($copy[$for]) ? 1 : 0);
        if ($count('browser') + $count('viewer') === 0) {
            return $content;
        }
        // Who has one thing only (a teleport, the map, or the address), who a menu
        $only = fn (string $kind) => array_values(array_filter(self::CLIENTS, fn ($for) => $count($for) === 1 && isset(['go' => $go, 'map' => $map, 'copy' => $copy][$kind][$for])));
        $menu = array_values(array_filter(self::CLIENTS, fn ($for) => $count($for) > 1));

        // The main link of the client told, none where that client has none (a grid alone in a browser): the text, the script giving
        // it the address of the client it finds
        $main = array_map(fn (array $list) => (string) $list[0]['link'], $go);
        $href = $main[$client] ?? '';
        $door = $only('go') === [] ? '' : '&#8288;<span class="w4os-tp-go"'.self::scope($only('go')).'>'.self::icon('go').'</span>';
        $out = $main === []
            ? '<span class="w4os-tp-main">'.$content.'</span>'
            : '<a class="w4os-tp-main"'.($href === '' ? '' : ' href="'.self::e($href).'"').self::data('browser', $main['browser'] ?? '').self::data('viewer', $main['viewer'] ?? '').'>'.$content.$door.'</a>';
        foreach ($only('map') as $for) {
            $label = self::e((string) ($map[$for][0]['label'] ?? ''));
            $out .= '&#8288;<a class="w4os-tp-action w4os-tp-map" data-for="'.$for.'" href="'.self::e((string) $map[$for][0]['link']).'" aria-label="'.$label.'" title="'.$label.'">'.self::icon('map').'</a>';
        }
        if ($only('copy') !== []) {
            $first = $copy[$only('copy')[0]];
            $out .= '&#8288;<button type="button" class="w4os-tp-action w4os-tp-copy"'.self::scope($only('copy')).self::copyData($first, $texts).' aria-label="'.self::e((string) ($first['label'] ?? '')).'" title="'.self::e((string) ($first['label'] ?? '')).'">'.self::icon('copy').self::icon('done').'</button>';
        }
        if ($menu !== []) {
            // Every teleport of the client, its main one first and marked, then its map, then its address to copy
            $items = '';
            foreach ($menu as $for) {
                foreach ($go[$for] ?? [] as $at => $link) {
                    $items .= '<a role="menuitem" class="w4os-tp-item'.($at === 0 ? ' is-default' : '').'" data-for="'.$for.'"'.($at === 0 ? ' aria-current="true"' : '').' href="'.self::e((string) $link['link']).'">'.self::icon('go').'<span class="w4os-tp-label">'.self::e((string) ($link['label'] ?? '')).'</span></a>';
                }
            }
            foreach ($menu as $for) {
                foreach ($map[$for] ?? [] as $link) {
                    $items .= '<a role="menuitem" class="w4os-tp-item w4os-tp-map" data-for="'.$for.'" href="'.self::e((string) $link['link']).'">'.self::icon('map').'<span class="w4os-tp-label">'.self::e((string) ($link['label'] ?? '')).'</span></a>';
                }
            }
            $copiers = array_values(array_filter($menu, fn ($for) => isset($copy[$for])));
            if ($copiers !== []) {
                $first = $copy[$copiers[0]];
                $items .= '<button type="button" role="menuitem" class="w4os-tp-item w4os-tp-copy"'.self::scope($copiers).self::copyData($first, $texts).'>'.self::icon('copy').self::icon('done').'<span class="w4os-tp-label">'.self::e((string) ($first['label'] ?? '')).'</span></button>';
            }
            $more = (string) ($texts['more'] ?? '');
            $out .= '&#8288;<span class="w4os-tp-more"'.self::scope($menu).'><button type="button" class="w4os-tp-toggle" aria-haspopup="true" aria-expanded="false" aria-label="'.self::e($more).'" title="'.self::e($more).'">'.self::icon('more').'</button>'
                .'<span class="w4os-tp-menu" role="menu" hidden>'.$items.'</span></span>';
        }

        return '<span class="w4os-tp" data-w4os-tp data-client="'.$client.'"'.($pattern === '' ? '' : ' data-viewer-pattern="'.self::e($pattern).'"').'>'.$out.'</span>';
    }

    /** What a link does: its action, else its format tells it (an engine before actions). @param array<string,mixed> $link */
    private static function action(array $link): string
    {
        $action = (string) ($link['action'] ?? '');
        if (in_array($action, ['teleport', 'map', 'copy'], true)) {
            return $action;
        }

        return match ((int) ($link['format'] ?? 0)) {
            self::COPY => 'copy',
            self::MAP => 'map',
            default => 'teleport',
        };
    }

    /** An icon, decorative: the text beside it, or the label of its button, says what it does. */
    private static function icon(string $name): string
    {
        return '<svg class="w4os-tp-icon w4os-tp-icon-'.$name.'" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.self::ICONS[$name].'</svg>';
    }

    /** The clients an element is for: none said when it is for both. @param list<string> $clients */
    private static function scope(array $clients): string
    {
        return count($clients) === count(self::CLIENTS) ? '' : ' data-for="'.$clients[0].'"';
    }

    /** @param array{link:string} $copy @param array{copied?: string} $texts */
    private static function copyData(array $copy, array $texts): string
    {
        return ' data-w4os-tp-copy="'.self::e((string) $copy['link']).'" data-copied="'.self::e((string) ($texts['copied'] ?? '')).'"';
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
