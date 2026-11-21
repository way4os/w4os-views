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

describe('a teleport link', function () {
    test('has the main link of the client it is seen from, the other links of each client in its menu, and the address to copy', function () {
        $browser = TeleportLink::html(grand_place(), 'Grand Place', 'browser', ['more' => 'More ways to go there', 'copied' => 'Copied'], 'Firestorm|SecondLife');
        $viewer = TeleportLink::html(grand_place(), 'Grand Place', 'viewer', ['more' => 'More ways to go there', 'copied' => 'Copied']);

        expect($browser)->toContain('<span class="w4os-tp" data-w4os-tp data-client="browser" data-viewer-pattern="Firestorm|SecondLife">')
            ->and($browser)->toContain('<a class="w4os-tp-main" href="secondlife://speculoos.world:8002/secondlife/Grand%20Place/128/128/21" data-browser="secondlife://speculoos.world:8002/secondlife/Grand%20Place/128/128/21" data-viewer="secondlife:///app/teleport/speculoos.world:8002:Grand%20Place/128/128/21">Grand Place</a>')
            ->and($browser)->toContain('<a role="menuitem" class="w4os-tp-item" data-for="browser" href="hop://speculoos.world:8002/Grand%20Place/128/128/21">hop:// link</a>')
            ->and($browser)->toContain('<a role="menuitem" class="w4os-tp-item" data-for="viewer" href="secondlife://speculoos.world:8002/secondlife/Grand%20Place/128/128/21">secondlife:// link</a>')
            ->and($browser)->toContain('<button type="button" role="menuitem" class="w4os-tp-item w4os-tp-copy" data-w4os-tp-copy="speculoos.world:8002:Grand Place/128/128/21" data-copied="Copied">Copy the address</button>')
            ->and($browser)->toContain('aria-haspopup="true" aria-expanded="false" aria-label="More ways to go there"', '<span class="w4os-tp-menu" role="menu" hidden>')
            ->and(substr_count($browser, 'class="w4os-tp-item w4os-tp-copy"'))->toBe(1)
            // Seen from the viewer: its own main link
            ->and($viewer)->toContain('data-client="viewer"', '<a class="w4os-tp-main" href="secondlife:///app/teleport/speculoos.world:8002:Grand%20Place/128/128/21"');
    });

    test('escapes its text and its addresses; a destination without links is its text alone, one with the address only a menu to copy it', function () {
        $odd = ['browser' => [['format' => 8, 'link' => 'hop://a.org:8002/A"B<', 'label' => '<i>hop</i>'], ['format' => 1024, 'link' => 'a.org:8002:A"B<', 'label' => 'Copy']]];

        expect(TeleportLink::html($odd, 'A & "B" <c>'))->toContain('>A &amp; &quot;B&quot; &lt;c&gt;</a>', 'href="hop://a.org:8002/A&quot;B&lt;"', 'data-w4os-tp-copy="a.org:8002:A&quot;B&lt;"')->not->toContain('<i>', '<c>')
            ->and(TeleportLink::html([], 'Nowhere <x>'))->toBe('Nowhere &lt;x&gt;')
            ->and(TeleportLink::html(['browser' => [['format' => 1024, 'link' => 'a.org:8002', 'label' => 'Copy']]], 'A grid'))->toContain('<span class="w4os-tp-main">A grid</span>', 'w4os-tp-copy');
    });

    test('has no link of the other client as its main link: a grid alone in a browser is its text, its viewer link kept for the script', function () {
        $grid = ['browser' => [['format' => 1024, 'link' => 'demo.w4os.org:3405', 'label' => 'Copy']], 'viewer' => [['format' => 32, 'link' => 'secondlife:///app/teleport/demo.w4os.org:3405', 'label' => 'Teleport'], ['format' => 1024, 'link' => 'demo.w4os.org:3405', 'label' => 'Copy']]];

        expect(TeleportLink::html($grid, 'Go', 'browser'))->toContain('<a class="w4os-tp-main" data-viewer="secondlife:///app/teleport/demo.w4os.org:3405">Go</a>')->not->toContain('href="secondlife')
            ->and(TeleportLink::html($grid, 'Go', 'viewer'))->toContain('<a class="w4os-tp-main" href="secondlife:///app/teleport/demo.w4os.org:3405"');
    });

    test('is inline: the chevron joined to its text (no line break between them), the menu placed from it', function () {
        $html = TeleportLink::html(grand_place(), 'Grand Place', 'browser', ['more' => 'More']);

        expect($html)->toStartWith('<span class="w4os-tp" ')
            ->toContain('>Grand Place</a>&#8288;<span class="w4os-tp-more"><button type="button" class="w4os-tp-toggle"', '<svg class="w4os-tp-icon" viewBox="0 0 24 24" width="1em" height="1em"', 'stroke="currentColor"', '</button><span class="w4os-tp-menu" role="menu" hidden>')
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

        expect($css)->toContain('.w4os-tp { display: inline; }', '.w4os-tp-more { position: relative; display: inline; white-space: nowrap; }', '.w4os-tp-menu { position: absolute; left: 0;', '.w4os-tp-menu[hidden] { display: none; }', '.w4os-tp[data-client="browser"] [data-for="viewer"], .w4os-tp[data-client="viewer"] [data-for="browser"] { display: none; }', '.w4os-tp-main:not([href]) { color: inherit;')
            ->and($css)->not->toContain('w4os-tp-card')
            ->and($css)->not->toMatch('/#[0-9a-f]{3,8}\b|rgba?\(|--pico-|--q2-/i')
            // No block, flex or grid layout of the inline component, no right alignment
            ->and(preg_replace('/\.w4os-tp-menu[^{]*\{[^}]*\}|\.w4os-tp-item[^{]*\{[^}]*\}|\.w4os-tp-icon[^{]*\{[^}]*\}/', '', $css))->not->toMatch('/display: (block|flex|grid)|text-align: right|float:/');
    });

    test('have the script: the client told by the user agent, the menu kept within the window, closed by Escape or a click outside, the address selected without a clipboard', function () {
        $js = (string) file_get_contents(dirname(__DIR__, 2).'/assets/'.TeleportLink::ASSETS['js'][0]);

        expect($js)->toContain("new RegExp(tp.dataset.viewerPattern, 'i').test(navigator.userAgent)", "main.removeAttribute('href')", "classList.add('w4os-tp-menu-end')", "event.key === 'Escape'", 'tp.contains(event.target) || close(tp)', 'navigator.clipboard ?', 'input.select()');
    });
});
