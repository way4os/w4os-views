<?php

namespace W4OS\Views;

/**
 * A teleport link that fits where it is seen, the same anywhere a site prints one: its main link (the first the destination gives
 * for that client), then a small button opening a short menu of its other links for that client and "Copy the address".
 *
 * The links of both clients are written (a page kept in a cache is seen from a browser and from the viewer alike): the site's
 * script (js/tp-link.js of the plugin) tells the client from the user agent (data-viewer-pattern) and shows its links, the main
 * one's address too; without it, the client the server told (data-client).
 *
 *     TeleportLink::html(['browser' => $browser, 'viewer' => $viewer], 'Grand Place', 'browser', $texts, $pattern);
 *
 * Each link: `format` (its Destination format), `link`, `label` (its text in a menu, translated); the address to copy is the one
 * of format TXT (16). $texts: `more` (the label of the button), `copied` (said once copied).
 */
final class TeleportLink
{
    /** The format of the address to copy (Destination::TXT of the engine). */
    public const TXT = 16;

    /** The clients a destination gives links for. */
    public const CLIENTS = ['browser', 'viewer'];

    /**
     * @param array{browser?: list<array{format:int,link:string,label:string}>, viewer?: list<array{format:int,link:string,label:string}>} $links
     * @param array{more?: string, copied?: string} $texts
     */
    public static function html(array $links, string $label, string $client = 'browser', array $texts = [], string $pattern = ''): string
    {
        $client = in_array($client, self::CLIENTS, true) ? $client : 'browser';
        $copy = null;
        $main = [];
        $items = '';
        foreach (self::CLIENTS as $for) {
            $own = array_values(array_filter((array) ($links[$for] ?? []), fn ($link) => is_array($link) && (string) ($link['link'] ?? '') !== ''));
            foreach ($own as $link) {
                if ((int) ($link['format'] ?? 0) === self::TXT) {
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
            return self::e($label);
        }
        $href = $main[$client] ?? $main['browser'] ?? $main['viewer'] ?? '';
        $first = $href === ''
            ? '<span class="w4os-tp-main">'.self::e($label).'</span>'
            : '<a class="w4os-tp-main" href="'.self::e($href).'"'.self::data('browser', $main['browser'] ?? '').self::data('viewer', $main['viewer'] ?? '').'>'.self::e($label).'</a>';
        if ($copy !== null) {
            $items .= '<button type="button" role="menuitem" class="w4os-tp-item w4os-tp-copy" data-w4os-tp-copy="'.self::e((string) $copy['link']).'" data-copied="'.self::e((string) ($texts['copied'] ?? '')).'">'.self::e((string) ($copy['label'] ?? '')).'</button>';
        }
        $more = (string) ($texts['more'] ?? '');

        return '<span class="w4os-tp" data-w4os-tp data-client="'.$client.'"'.($pattern === '' ? '' : ' data-viewer-pattern="'.self::e($pattern).'"').'>'.$first
            .($items === '' ? '' : '<button type="button" class="w4os-tp-toggle" aria-haspopup="true" aria-expanded="false" aria-label="'.self::e($more).'" title="'.self::e($more).'"><span aria-hidden="true">&#9662;</span></button>'
                .'<span class="w4os-tp-menu" role="menu" hidden>'.$items.'</span>')
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
