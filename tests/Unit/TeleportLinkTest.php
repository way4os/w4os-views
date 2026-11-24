<?php

use W4OS\Views\TeleportLink;

/** The links of Grand Place as the engine gives them (GET /grids/{grid}/place), their labels translated. */
function grand_place(): array
{
    $txt = ['format' => 1024, 'link' => 'speculoos.world:8002:Grand Place/128/128/21', 'label' => 'Copy the address'];

    return [
        'browser' => [
            ['format' => 2048, 'link' => 'secondlife://speculoos.world:8002/secondlife/Grand%20Place/128/128/21', 'label' => 'secondlife:// link'],
            ['format' => 8, 'link' => 'hop://speculoos.world:8002/Grand%20Place/128/128/21', 'label' => 'hop:// link'],
            $txt,
        ],
        'viewer' => [
            ['format' => 32, 'link' => 'secondlife:///app/teleport/speculoos.world:8002:Grand%20Place/128/128/21', 'label' => 'Teleport in the viewer'],
            ['format' => 2048, 'link' => 'secondlife://speculoos.world:8002/secondlife/Grand%20Place/128/128/21', 'label' => 'secondlife:// link'],
            $txt,
        ],
    ];
}

/** The markup of a teleport link without its icons, to read. */
function without_icons(string $html): string
{
    return (string) preg_replace('#<svg class="w4os-tp-icon w4os-tp-icon-([a-z]+)"[^>]*>.*?</svg>#', '[$1]', $html);
}

/** The items of a teleport link's menu as their icon and their text, in their order. */
function tp_items(string $html): array
{
    return preg_match_all('#\[(add|go|map|copy)\](?:\[done\])?<span class="w4os-tp-label">([^<]+)<#', $html, $items) ? array_map(fn ($icon, $label) => "$icon $label", $items[1], $items[2]) : [];
}

describe('a teleport link', function () {
    test('has the main link of the client it is seen from, and a menu of all its links, the main one first and marked, then the address to copy', function () {
        $browser = without_icons(TeleportLink::html(grand_place(), 'Grand Place', 'browser', ['more' => 'More ways to go there', 'copied' => 'Copied'], 'Firestorm|SecondLife'));
        $viewer = without_icons(TeleportLink::html(grand_place(), 'Grand Place', 'viewer', ['more' => 'More ways to go there', 'copied' => 'Copied']));

        expect($browser)->toContain('<span class="w4os-tp" data-w4os-tp data-client="browser" data-viewer-pattern="Firestorm|SecondLife">')
            ->and($browser)->toContain('<a class="w4os-tp-main" href="secondlife://speculoos.world:8002/secondlife/Grand%20Place/128/128/21" data-browser="secondlife://speculoos.world:8002/secondlife/Grand%20Place/128/128/21" data-viewer="secondlife:///app/teleport/speculoos.world:8002:Grand%20Place/128/128/21">Grand Place</a>')
            // Its main link in the menu too, never hidden, each teleport with its door
            ->and($browser)->toContain('<a role="menuitem" class="w4os-tp-item is-default" data-for="browser" aria-current="true" href="secondlife://speculoos.world:8002/secondlife/Grand%20Place/128/128/21">[go]<span class="w4os-tp-label">secondlife:// link</span></a><a role="menuitem" class="w4os-tp-item" data-for="browser" href="hop://speculoos.world:8002/Grand%20Place/128/128/21">[go]<span class="w4os-tp-label">hop:// link</span></a>')
            ->and($browser)->toContain('<a role="menuitem" class="w4os-tp-item is-default" data-for="viewer" aria-current="true" href="secondlife:///app/teleport/speculoos.world:8002:Grand%20Place/128/128/21">[go]<span class="w4os-tp-label">Teleport in the viewer</span></a>')
            ->and($browser)->toContain('<button type="button" role="menuitem" class="w4os-tp-item w4os-tp-copy" data-for="browser" data-w4os-tp-copy="speculoos.world:8002:Grand Place/128/128/21" data-copied="Copied">[copy][done]<span class="w4os-tp-label">Copy the address</span></button>', 'data-for="viewer" data-w4os-tp-copy="speculoos.world:8002:Grand Place/128/128/21" data-copied="Copied">[copy][done]<span class="w4os-tp-label">Copy the address</span></button></span></span></span>')
            ->and($browser)->toContain('<span class="w4os-tp-more"><button type="button" class="w4os-tp-toggle" aria-haspopup="true" aria-expanded="false" aria-label="More ways to go there" title="More ways to go there">[more]</button><span class="w4os-tp-menu" role="menu" hidden>')
            ->and(substr_count($browser, 'class="w4os-tp-item w4os-tp-copy" data-for="browser"'))->toBe(1)
            ->and($browser)->not->toContain('w4os-tp-action', 'w4os-tp-go"')
            // Seen from the viewer: its own main link
            ->and($viewer)->toContain('data-client="viewer"', '<a class="w4os-tp-main" href="secondlife:///app/teleport/speculoos.world:8002:Grand%20Place/128/128/21"');
    });

    test('has no menu for one thing only: a teleport its door after the text, in the link; the address alone its copy button', function () {
        $teleport = without_icons(TeleportLink::html(['browser' => [['format' => 8, 'link' => 'hop://a.org:80/R', 'label' => 'hop']], 'viewer' => [['format' => 32, 'link' => 'secondlife:///app/teleport/a.org:80:R', 'label' => 'Teleport']]], 'R'));
        $copy = without_icons(TeleportLink::html(['browser' => [['format' => 1024, 'link' => 'a.org:80', 'label' => 'Copy the address']]], 'A grid', 'browser', ['copied' => 'Copied']));

        expect($teleport)->toContain('>R&#8288;<span class="w4os-tp-go">[go]</span></a></span>')->not->toContain('w4os-tp-more', 'w4os-tp-toggle', 'w4os-tp-menu')
            ->and($copy)->toBe('<span class="w4os-tp" data-w4os-tp data-client="browser"><span class="w4os-tp-main">A grid</span>&#8288;<button type="button" class="w4os-tp-action w4os-tp-copy" data-for="browser" data-w4os-tp-copy="a.org:80" data-copied="Copied" aria-label="Copy the address" title="Copy the address">[copy][done]</button></span>');
    });

    test('shows the map between its teleports and the address to copy, or its icon alone when it is all a client has', function () {
        $place = grand_place();
        array_splice($place['viewer'], 2, 0, [['format' => 64, 'link' => 'secondlife:///app/worldmap/speculoos.world:8002:Grand%20Place/128/128/21', 'label' => 'Show on the map']]);
        $menu = without_icons(TeleportLink::html($place, 'Grand Place', 'viewer'));
        $alone = without_icons(TeleportLink::html(['viewer' => [['format' => 64, 'link' => 'secondlife:///app/worldmap/a.org:80:R', 'label' => 'Show on the map']]], 'R', 'viewer'));

        expect($menu)->toContain('<span class="w4os-tp-label">secondlife:// link</span></a><a role="menuitem" class="w4os-tp-item w4os-tp-map" data-for="viewer" href="secondlife:///app/worldmap/speculoos.world:8002:Grand%20Place/128/128/21">[map]<span class="w4os-tp-label">Show on the map</span></a><button type="button" role="menuitem" class="w4os-tp-item w4os-tp-copy"')
            ->and(substr_count($menu, 'w4os-tp-map'))->toBe(1)
            ->and($alone)->toContain('<span class="w4os-tp-main">R</span>&#8288;<a class="w4os-tp-action w4os-tp-map" data-for="viewer" href="secondlife:///app/worldmap/a.org:80:R" aria-label="Show on the map" title="Show on the map">[map]</a>')->not->toContain('w4os-tp-toggle');
    });

    test('tells what a link does by its action, any format: a teleport its door, each map in its place with its icon, the copy last', function () {
        $links = ['viewer' => [
            ['action' => 'teleport', 'format' => 4096, 'link' => 'hop:///app/teleport/a.org:80:R', 'label' => 'Teleport (hop app)'],
            ['action' => 'copy', 'format' => 16, 'link' => 'a.org:80:R', 'label' => 'Copy the address'],
            ['action' => 'map', 'format' => 8192, 'link' => 'hop:///app/worldmap/a.org:80:R', 'label' => 'Map (hop app)'],
            ['action' => 'map', 'format' => 64, 'link' => 'secondlife:///app/worldmap/a.org:80:R', 'label' => 'Show on the map'],
            ['action' => 'teleport', 'format' => 1024, 'link' => 'secondlife:///app/teleport/a.org:80:R', 'label' => 'Teleport'],
        ]];
        $html = without_icons(TeleportLink::html($links, 'R', 'viewer'));

        expect($html)->toContain('<a class="w4os-tp-main" href="hop:///app/teleport/a.org:80:R"')
            ->and(preg_match_all('#\[(go|map|copy)\](?:\[done\])?<span class="w4os-tp-label">([^<]+)<#', $html, $items) ? array_map(fn ($icon, $label) => "$icon $label", $items[1], $items[2]) : [])
            ->toBe(['go Teleport (hop app)', 'go Teleport', 'map Map (hop app)', 'map Show on the map', 'copy Copy the address']);
    });

    test('lists its links in one order (teleports, maps, the address to copy, adding the grid last), its main one marked where it stands', function () {
        $grid = ['viewer' => [
            ['action' => 'addgrid', 'format' => 4096, 'link' => 'secondlife:///app/gridmanager/addgrid/dev-demo.w4os.org%3A3405', 'label' => 'Add the grid'],
            ['action' => 'teleport', 'format' => 32, 'link' => 'secondlife:///app/teleport/dev-demo.w4os.org:3405', 'label' => 'Teleport'],
            ['action' => 'copy', 'format' => 1024, 'link' => 'dev-demo.w4os.org:3405', 'label' => 'Copy the address'],
            ['action' => 'map', 'format' => 64, 'link' => 'secondlife:///app/worldmap/dev-demo.w4os.org:3405', 'label' => 'Show on the map'],
        ]];
        $html = without_icons(TeleportLink::html($grid, 'dev-demo.w4os.org:3405', 'viewer'));

        // Its main link the destination's first that goes there (adding the grid, for a login address), marked at its place, last
        expect($html)->toContain('<a class="w4os-tp-main" href="secondlife:///app/gridmanager/addgrid/dev-demo.w4os.org%3A3405" data-viewer="secondlife:///app/gridmanager/addgrid/dev-demo.w4os.org%3A3405">dev-demo.w4os.org:3405</a>')
            ->and(tp_items($html))->toBe(['go Teleport', 'map Show on the map', 'copy Copy the address', 'add Add the grid'])
            ->and($html)->toContain('class="w4os-tp-item is-default" data-for="viewer" aria-current="true" href="secondlife:///app/gridmanager/addgrid/')
            ->and(substr_count($html, 'is-default'))->toBe(1);
    });

    test('takes for its main link the action a page prefers, its menu in the same order; the destination\'s first without it', function () {
        $grid = ['browser' => [
            ['action' => 'addgrid', 'link' => 'hop:///app/gridmanager/addgrid/a.org%3A80', 'label' => 'Add the grid'],
            ['action' => 'teleport', 'link' => 'hop://a.org:80/', 'label' => 'Teleport (hop://)'],
            ['action' => 'teleport', 'link' => 'secondlife://a.org:80/secondlife/', 'label' => 'Teleport (secondlife://)'],
            ['action' => 'copy', 'link' => 'a.org:80', 'label' => 'Copy the address'],
        ]];
        $visit = without_icons(TeleportLink::html($grid, 'Go', 'browser', [], '', null, 'teleport'));

        expect($visit)->toContain('<a class="w4os-tp-main" href="hop://a.org:80/"', 'class="w4os-tp-item is-default" data-for="browser" aria-current="true" href="hop://a.org:80/"')
            ->and(tp_items($visit))->toBe(['go Teleport (hop://)', 'go Teleport (secondlife://)', 'copy Copy the address', 'add Add the grid'])
            ->and(tp_items(without_icons(TeleportLink::html($grid, 'Go'))))->toBe(tp_items($visit))
            ->and(TeleportLink::html($grid, 'Go'))->toContain('<a class="w4os-tp-main" href="hop:///app/gridmanager/addgrid/a.org%3A80"')
            // An action the destination has not: its first that goes there
            ->and(TeleportLink::html(['browser' => [$grid['browser'][0], $grid['browser'][3]]], 'Go', 'browser', [], '', null, 'teleport'))->toContain('<a class="w4os-tp-main" href="hop:///app/gridmanager/addgrid/a.org%3A80"');
    });

    test('keeps only the actions a page asks: one left, its icon without a menu (the address to copy alone, the text not a link)', function () {
        $grid = ['browser' => [
            ['action' => 'addgrid', 'link' => 'hop:///app/gridmanager/addgrid/a.org%3A80', 'label' => 'Add the grid'],
            ['action' => 'teleport', 'link' => 'hop://a.org:80/', 'label' => 'Teleport (hop://)'],
            ['action' => 'copy', 'link' => 'a.org:80', 'label' => 'Copy the address'],
        ]];
        $add = without_icons(TeleportLink::html($grid, 'a.org:80', 'browser', [], '', null, 'addgrid', ['addgrid', 'copy']));
        $copy = without_icons(TeleportLink::html($grid, 'a.org:80', 'browser', ['copied' => 'Copied'], '', null, null, ['copy']));
        $go = without_icons(TeleportLink::html($grid, 'Go', 'browser', [], '', null, 'teleport', ['teleport', 'map']));

        expect(tp_items($add))->toBe(['copy Copy the address', 'add Add the grid'])
            ->and($add)->toContain('<a class="w4os-tp-main" href="hop:///app/gridmanager/addgrid/a.org%3A80"')->not->toContain('Teleport')
            ->and($copy)->toBe('<span class="w4os-tp" data-w4os-tp data-client="browser"><span class="w4os-tp-main">a.org:80</span>&#8288;<button type="button" class="w4os-tp-action w4os-tp-copy" data-for="browser" data-w4os-tp-copy="a.org:80" data-copied="Copied" aria-label="Copy the address" title="Copy the address">[copy][done]</button></span>')
            ->and($go)->toContain('>Go&#8288;<span class="w4os-tp-go" data-for="browser">[go]</span></a>')->not->toContain('w4os-tp-toggle', 'addgrid', 'w4os-tp-copy');
    });

    test('escapes its text and its addresses; a destination without links is its text alone', function () {
        $odd = ['browser' => [['format' => 8, 'link' => 'hop://a.org:8002/A"B<', 'label' => '<i>hop</i>'], ['format' => 1024, 'link' => 'a.org:8002:A"B<', 'label' => 'Copy']]];

        expect(TeleportLink::html($odd, 'A & "B" <c>'))->toContain('>A &amp; &quot;B&quot; &lt;c&gt;</a>', 'href="hop://a.org:8002/A&quot;B&lt;"', 'data-w4os-tp-copy="a.org:8002:A&quot;B&lt;"', '&lt;i&gt;hop&lt;/i&gt;')->not->toContain('<i>', '<c>')
            ->and(TeleportLink::html([], 'Nowhere <x>'))->toBe('Nowhere &lt;x&gt;');
    });

    test('has no link of the other client: a grid alone in a browser is its text and its copy button, the viewer its teleport and its menu', function () {
        $grid = ['browser' => [['format' => 1024, 'link' => 'demo.w4os.org:3405', 'label' => 'Copy']], 'viewer' => [['format' => 32, 'link' => 'secondlife:///app/teleport/demo.w4os.org:3405', 'label' => 'Teleport'], ['format' => 1024, 'link' => 'demo.w4os.org:3405', 'label' => 'Copy']]];
        $browser = without_icons(TeleportLink::html($grid, 'Go', 'browser'));

        expect($browser)->toContain('<a class="w4os-tp-main" data-viewer="secondlife:///app/teleport/demo.w4os.org:3405">Go</a>', '<button type="button" class="w4os-tp-action w4os-tp-copy" data-for="browser"', '<span class="w4os-tp-more" data-for="viewer">')->not->toContain('<a class="w4os-tp-main" href')
            ->and(TeleportLink::html($grid, 'Go', 'viewer'))->toContain('<a class="w4os-tp-main" href="secondlife:///app/teleport/demo.w4os.org:3405"');
    });

    test('is inline: its icon joined to its text (no line break between them), the menu placed from it', function () {
        $html = TeleportLink::html(grand_place(), 'Grand Place', 'browser', ['more' => 'More']);

        expect($html)->toStartWith('<span class="w4os-tp" ')
            ->toContain('>Grand Place</a>&#8288;<span class="w4os-tp-more"><button type="button" class="w4os-tp-toggle"', '<svg class="w4os-tp-icon w4os-tp-icon-more" viewBox="0 0 24 24" width="1em" height="1em"', 'stroke="currentColor"', '<path d="M13 12v.01"/>', '</button><span class="w4os-tp-menu" role="menu" hidden>')
            ->not->toContain('&#9662;', '…', '<div');
    });

    test('takes markup of the caller as its content (a picture and a name), the chevron and its menu beside the link, never in it', function () {
        $html = TeleportLink::html(grand_place(), 'Grand Place', 'browser', ['more' => 'More'], '', '<img src="p.jpg" alt=""> <span>Grand Place</span>');

        expect($html)->toStartWith('<span class="w4os-tp" ')
            ->toContain('href="secondlife://speculoos.world:8002/secondlife/Grand%20Place/128/128/21" data-browser=', '><img src="p.jpg" alt=""> <span>Grand Place</span></a>&#8288;<span class="w4os-tp-more"><button')
            ->not->toContain('w4os-tp-card')
            ->and(substr_count($html, '<a class="w4os-tp-main"'))->toBe(1)
            ->and(strpos($html, 'w4os-tp-toggle'))->toBeGreaterThan(strpos($html, '</a>'))
            ->and(TeleportLink::html([], 'Grand Place', 'browser', [], '', '<b>Grand Place</b>'))->toBe('<b>Grand Place</b>');
    });
});

describe('the assets of a teleport link', function () {
    test('are in the package for any bridge to serve: an inline style without colours of its own, the same wherever it is', function () {
        $css = (string) file_get_contents(dirname(__DIR__, 2).'/assets/'.TeleportLink::ASSETS['css'][0]);

        expect($css)->toContain('.w4os-tp { display: inline; }', '.w4os-tp-more { position: relative; display: inline; white-space: nowrap; }', '.w4os-tp-menu { position: absolute; left: 0;', '.w4os-tp-menu[hidden] { display: none; }', '.w4os-tp[data-client="browser"] [data-for="viewer"], .w4os-tp[data-client="viewer"] [data-for="browser"] { display: none; }', '.w4os-tp-main:not([href]) { color: inherit;', '.w4os-tp-menu .w4os-tp-item { display: flex;')
            ->and($css)->not->toContain('w4os-tp-card')
            ->and($css)->not->toMatch('/#[0-9a-f]{3,8}\b|rgba?\(|--pico-|--q2-/i')
            // No block, flex or grid layout of the inline component, no right alignment
            ->and(preg_replace('/[^{}]*(w4os-tp-menu|w4os-tp-item|w4os-tp-icon)[^{}]*\{[^}]*\}/', '', $css))->not->toMatch('/display: (block|flex|grid)|text-align: right|float:/');
    });

    test('have the script: the client told by the user agent, the menu kept within the window, closed by Escape or a click outside, the address copied and said, or selected without a clipboard', function () {
        $js = (string) file_get_contents(dirname(__DIR__, 2).'/assets/'.TeleportLink::ASSETS['js'][0]);

        expect($js)->toContain("new RegExp(tp.dataset.viewerPattern, 'i').test(navigator.userAgent)", "main.removeAttribute('href')", "classList.add('w4os-tp-menu-end')", "event.key === 'Escape'", 'tp.contains(event.target) || close(tp)', 'input.select()', "copy.classList.add('is-copied')");
    });

    test('copy in one click on a page of http too: the clipboard of the browser, else the copy of a selected text, the address shown selected only when both fail', function () {
        $js = (string) file_get_contents(dirname(__DIR__, 2).'/assets/'.TeleportLink::ASSETS['js'][0]);
        $clipboard = strpos($js, 'navigator.clipboard.writeText');
        $command = strpos($js, "document.execCommand('copy')");

        // A page of http has no navigator.clipboard (a secure context only): the copy of a text selected out of sight
        expect($clipboard)->not->toBeFalse()
            ->and($command)->not->toBeFalse()
            ->and($js)->toContain('window.isSecureContext', '.catch(fallback)')
            // The field to copy by hand comes last, when nothing could copy
            ->and((int) strpos($js, 'const copied = ') < (int) strpos($js, 'select(copy, text)'))->toBeTrue();
    });
});
