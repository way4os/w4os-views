<?php

use W4OS\Views\TeleportLink;

/** The links of Grand Place as the engine gives them (GET /grids/{grid}/place), their labels translated. */
function grand_place(): array
{
    $txt = ['format' => 16, 'link' => 'speculoos.world:8002 Grand Place/128/128/21', 'label' => 'Copy the address'];

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
            ->and($browser)->toContain('<button type="button" role="menuitem" class="w4os-tp-item w4os-tp-copy" data-w4os-tp-copy="speculoos.world:8002 Grand Place/128/128/21" data-copied="Copied">Copy the address</button>')
            ->and($browser)->toContain('aria-haspopup="true" aria-expanded="false" aria-label="More ways to go there"', '<span class="w4os-tp-menu" role="menu" hidden>')
            ->and(substr_count($browser, 'class="w4os-tp-item w4os-tp-copy"'))->toBe(1)
            // Seen from the viewer: its own main link
            ->and($viewer)->toContain('data-client="viewer"', '<a class="w4os-tp-main" href="secondlife:///app/teleport/speculoos.world:8002:Grand%20Place/128/128/21"');
    });

    test('escapes its text and its addresses; a destination without links is its text alone, one with the address only a menu to copy it', function () {
        $odd = ['browser' => [['format' => 8, 'link' => 'hop://a.org:8002/A"B<', 'label' => '<i>hop</i>'], ['format' => 16, 'link' => 'a.org:8002 A"B<', 'label' => 'Copy']]];

        expect(TeleportLink::html($odd, 'A & "B" <c>'))->toContain('>A &amp; &quot;B&quot; &lt;c&gt;</a>', 'href="hop://a.org:8002/A&quot;B&lt;"', 'data-w4os-tp-copy="a.org:8002 A&quot;B&lt;"')->not->toContain('<i>', '<c>')
            ->and(TeleportLink::html([], 'Nowhere <x>'))->toBe('Nowhere &lt;x&gt;')
            ->and(TeleportLink::html(['browser' => [['format' => 16, 'link' => 'a.org:8002', 'label' => 'Copy']]], 'A grid'))->toContain('<span class="w4os-tp-main">A grid</span>', 'w4os-tp-copy');
    });
});
