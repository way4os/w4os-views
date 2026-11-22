<?php

namespace W4OS\Views;

/**
 * A teleport link that fits where it is seen, the same anywhere a site prints one: its main link (the first link the destination
 * gives for that client that goes there, adding the grid to the viewer for its login address; or the one of the action the page
 * prefers, a teleport for a visit from another grid), then what else it can do for that client, the actions the page keeps of
 * them (`only`). One thing only: its icon after the text, no menu (the door of a teleport, the globe that adds the grid, the map,
 * or the copy button where the address is all there is). More: a chevron opening the menu of all of them in one order (the
 * teleports, the maps, "Copy the address", adding the grid last), the main link marked where it stands.
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
 * Each link: `action` (addgrid, teleport, map or copy: what it does), `format` (its Destination format), `link`, `label` (its text in a
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

    /** Its icons (Tabler 3.52: chevron-down, world-plus, door-enter, map, copy, check), at the size and in the colour of the text. */
    private const ICONS = [
        'more' => '<path d="M6 9l6 6l6 -6"/>',
        'add' => '<path d="M20.985 12.518a9 9 0 1 0 -8.45 8.466"/><path d="M3.6 9h16.8"/><path d="M3.6 15h11.4"/><path d="M11.5 3a17 17 0 0 0 0 18"/><path d="M12.5 3a16.998 16.998 0 0 1 2.283 12.157"/><path d="M16 19h6"/><path d="M19 16v6"/>',
        'go' => '<path d="M13 12v.01"/><path d="M3 21h18"/><path d="M5 21v-16a2 2 0 0 1 2 -2h6m4 10.5v7.5"/><path d="M21 7h-7m3 -3l-3 3l3 3"/>',
        'map' => '<path d="M3 7l6 -3l6 3l6 -3v13l-6 3l-6 -3l-6 3v-13"/><path d="M9 4v13"/><path d="M15 7v13"/>',
        'copy' => '<path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666"/><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1"/>',
        'done' => '<path d="M5 12l5 5l10 -10"/>',
    ];

    /** The order of a menu by what its links do: the teleports, the maps, the address to copy, adding the grid to the viewer. */
    private const ORDER = ['teleport' => 0, 'map' => 1, 'copy' => 2, 'addgrid' => 3];

    /** The icon of what a link does. */
    private const ICON_OF = ['teleport' => 'go', 'addgrid' => 'add', 'map' => 'map', 'copy' => 'copy'];

    /**
     * @param array{browser?: list<array{action?:string,format?:int,link:string,label:string}>, viewer?: list<array{action?:string,format?:int,link:string,label:string}>} $links
     * @param array{more?: string, copied?: string} $texts
     * @param string|null $html the content of the main link in place of its label, markup the caller has escaped (a picture and
     *                          a name)
     * @param string|null $prefer the action of its main link where the destination gives one (teleport: a visit from another
     *                            grid; addgrid: a link that adds the grid), else the first link that goes there; marked in its
     *                            menu where it stands
     * @param list<string>|null $only the actions it keeps (copy alone: the address to paste in a map), all of them when null
     */
    public static function html(array $links, string $label, string $client = 'browser', array $texts = [], string $pattern = '', ?string $html = null, ?string $prefer = null, ?array $only = null): string
    {
        $client = in_array($client, self::CLIENTS, true) ? $client : 'browser';
        $content = $html ?? self::e($label);
        // What each client may do, the actions kept, one address to copy; its main link; its menu in one order whatever it is
        $items = $main = [];
        foreach (self::CLIENTS as $for) {
            $own = [];
            foreach ((array) ($links[$for] ?? []) as $link) {
                if (! is_array($link) || (string) ($link['link'] ?? '') === '') {
                    continue;
                }
                $does = self::action($link);
                if (($only !== null && ! in_array($does, $only, true)) || ($does === 'copy' && in_array('copy', array_column($own, 'does'), true))) {
                    continue;
                }
                $own[] = ['does' => $does] + $link;
            }
            $goes = array_values(array_filter($own, fn (array $link) => in_array($link['does'], ['teleport', 'addgrid'], true)));
            $chosen = array_values(array_filter($goes, fn (array $link) => $link['does'] === $prefer))[0] ?? $goes[0] ?? null;
            $chosen === null || $main[$for] = $chosen;
            usort($own, fn (array $a, array $b) => self::ORDER[$a['does']] <=> self::ORDER[$b['does']]);
            $own === [] || $items[$for] = $own;
        }
        if ($items === []) {
            return $content;
        }
        // Who has one thing only (its icon, no menu), who a menu
        $alone = fn (array $actions) => array_values(array_filter(self::CLIENTS, fn ($for) => count($items[$for] ?? []) === 1 && in_array($items[$for][0]['does'], $actions, true)));
        $menu = array_values(array_filter(self::CLIENTS, fn ($for) => count($items[$for] ?? []) > 1));

        // The main link of the client told, none where that client has none (a grid alone in a browser): the text, the script giving
        // it the address of the client it finds
        $href = (string) ($main[$client]['link'] ?? '');
        $icons = [];
        foreach ($alone(['teleport', 'addgrid']) as $for) {
            $icons[$for] = self::ICON_OF[$items[$for][0]['does']];
        }
        $door = count($icons) === count(self::CLIENTS) && count(array_unique($icons)) === 1
            ? '&#8288;<span class="w4os-tp-go">'.self::icon((string) reset($icons)).'</span>'
            : implode('', array_map(fn ($for, $icon) => '&#8288;<span class="w4os-tp-go" data-for="'.$for.'">'.self::icon($icon).'</span>', array_keys($icons), $icons));
        $out = $main === []
            ? '<span class="w4os-tp-main">'.$content.'</span>'
            : '<a class="w4os-tp-main"'.($href === '' ? '' : ' href="'.self::e($href).'"').self::data('browser', (string) ($main['browser']['link'] ?? '')).self::data('viewer', (string) ($main['viewer']['link'] ?? '')).'>'.$content.$door.'</a>';
        foreach ($alone(['map']) as $for) {
            $label = self::e((string) ($items[$for][0]['label'] ?? ''));
            $out .= '&#8288;<a class="w4os-tp-action w4os-tp-map" data-for="'.$for.'" href="'.self::e((string) $items[$for][0]['link']).'" aria-label="'.$label.'" title="'.$label.'">'.self::icon('map').'</a>';
        }
        $copiers = $alone(['copy']);
        if ($copiers !== []) {
            $first = $items[$copiers[0]][0];
            $out .= '&#8288;<button type="button" class="w4os-tp-action w4os-tp-copy"'.self::scope($copiers).self::copyData($first, $texts).' aria-label="'.self::e((string) ($first['label'] ?? '')).'" title="'.self::e((string) ($first['label'] ?? '')).'">'.self::icon('copy').self::icon('done').'</button>';
        }
        if ($menu !== []) {
            // Everything the client may do, in the one order, its main link marked where it stands
            $list = '';
            foreach ($menu as $for) {
                foreach ($items[$for] as $link) {
                    $text = '<span class="w4os-tp-label">'.self::e((string) ($link['label'] ?? '')).'</span>';
                    if ($link['does'] === 'copy') {
                        $list .= '<button type="button" role="menuitem" class="w4os-tp-item w4os-tp-copy" data-for="'.$for.'"'.self::copyData($link, $texts).'>'.self::icon('copy').self::icon('done').$text.'</button>';
                        continue;
                    }
                    $chosen = isset($main[$for]) && $main[$for]['link'] === $link['link'] && $main[$for]['does'] === $link['does'];
                    $list .= '<a role="menuitem" class="w4os-tp-item'.($link['does'] === 'map' ? ' w4os-tp-map' : '').($chosen ? ' is-default' : '').'" data-for="'.$for.'"'.($chosen ? ' aria-current="true"' : '').' href="'.self::e((string) $link['link']).'">'.self::icon(self::ICON_OF[$link['does']]).$text.'</a>';
                }
            }
            $more = (string) ($texts['more'] ?? '');
            $out .= '&#8288;<span class="w4os-tp-more"'.self::scope($menu).'><button type="button" class="w4os-tp-toggle" aria-haspopup="true" aria-expanded="false" aria-label="'.self::e($more).'" title="'.self::e($more).'">'.self::icon('more').'</button>'
                .'<span class="w4os-tp-menu" role="menu" hidden>'.$list.'</span></span>';
        }

        return '<span class="w4os-tp" data-w4os-tp data-client="'.$client.'"'.($pattern === '' ? '' : ' data-viewer-pattern="'.self::e($pattern).'"').'>'.$out.'</span>';
    }

    /** What a link does: its action, else its format tells it (an engine before actions). @param array<string,mixed> $link */
    private static function action(array $link): string
    {
        $action = (string) ($link['action'] ?? '');
        if (in_array($action, ['addgrid', 'teleport', 'map', 'copy'], true)) {
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
