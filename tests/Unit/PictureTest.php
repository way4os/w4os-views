<?php

use W4OS\Views\Picture;

describe('a picture of the grid', function () {
    test('of a person: its picture; without one, the silhouette, faint; one that cannot be loaded becomes the silhouette', function () {
        $shown = Picture::tag('/pictures/a1.jpg', '/pictures/none.jpg', 'w4os-thumb', 32);
        $none = Picture::tag(null, '/pictures/none.jpg', 'w4os-thumb', 32);

        expect($shown)->toBe('<span class="w4os-picture w4os-thumb" style="width: 32px; height: 32px"><img src="/pictures/a1.jpg" alt="" width="32" height="32" loading="lazy" onerror="this.onerror=null;this.src=\'/pictures/none.jpg\';this.style.opacity=\'0.5\';this.parentNode.classList.add(\'w4os-picture-none\')"></span>')
            ->and($none)->toBe('<span class="w4os-picture w4os-thumb w4os-picture-none" style="width: 32px; height: 32px"><img src="/pictures/none.jpg" alt="" width="32" height="32" loading="lazy" style="opacity: 0.5"></span>');
    });

    test('of a place: its picture, gone when it cannot be loaded; without one, nothing', function () {
        expect(Picture::tag('/pictures/p1.jpg', null, 'place-picture'))->toBe('<span class="w4os-picture place-picture"><img src="/pictures/p1.jpg" alt="" loading="lazy" onerror="this.parentNode.hidden=true"></span>')
            ->and(Picture::tag(null))->toBe('')
            ->and(Picture::tag(''))->toBe('');
    });

    test('never says a name, nor its initials', function () {
        expect((string) file_get_contents(dirname(__DIR__, 2).'/src/Picture.php'))->not->toContain('initials(', 'data-initials');
    });
});
